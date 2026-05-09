<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Models\Transaction;
use Exception;

class RecalculateBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:recalculate {--dry-run : Only show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recovers account initial balances and recalculates all transaction running balances from scratch.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("Starting balance recalculation process...");
        if ($isDryRun) {
            $this->warn("RUNNING IN DRY-RUN MODE. NO CHANGES WILL BE SAVED.");
        }

        try {
            DB::transaction(function () use ($isDryRun) {
                // Step 1: Recover True Initial Balances
                $this->info("Step 1: Recovering true initial balances for all accounts...");
                $this->recoverInitialBalances();

                // Step 2: Recalculate Running Balances chronologically
                $this->info("Step 2: Recalculating chronological running balances...");
                $this->recalculateRunningBalances();

                if ($isDryRun) {
                    throw new Exception("Dry run completed successfully. Rolling back changes.");
                }
            });

            if (!$isDryRun) {
                $this->info("Success! All accounts and transactions have been perfectly synchronized.");
            }
        } catch (Exception $e) {
            if ($e->getMessage() === "Dry run completed successfully. Rolling back changes.") {
                $this->info("Dry run finished. Database remains untouched.");
            } else {
                $this->error("An error occurred. All changes have been rolled back.");
                $this->error("Error message: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Calculates what the account's balance was before any transactions occurred,
     * updates the `initial_balance` column, and resets the current `balance` 
     * to prepare for chronological forward calculation.
     */
    private function recoverInitialBalances()
    {
        $accounts = DB::select('
            SELECT 
                a.id, a.name, a.balance as current_balance,
                COALESCE(SUM(CASE WHEN t.type = "credit" AND t.account_id = a.id THEN t.amount ELSE 0 END), 0) as total_credits,
                COALESCE(SUM(CASE WHEN t.type = "debit" AND t.account_id = a.id THEN t.amount ELSE 0 END), 0) as total_debits,
                COALESCE(SUM(CASE WHEN t.to_account_id = a.id THEN t.amount ELSE 0 END), 0) as transfers_in,
                COALESCE(SUM(CASE WHEN t.from_account_id = a.id THEN t.amount ELSE 0 END), 0) as transfers_out
            FROM accounts a
            LEFT JOIN transactions t ON (t.account_id = a.id OR t.from_account_id = a.id OR t.to_account_id = a.id)
            GROUP BY a.id, a.name, a.balance
        ');

        $bar = $this->output->createProgressBar(count($accounts));
        $bar->start();

        foreach ($accounts as $row) {
            // Formula: Initial = Current - Credits + Debits - Transfers In + Transfers Out
            $initial = $row->current_balance - $row->total_credits + $row->total_debits - $row->transfers_in + $row->transfers_out;
            $initial = max(0, round($initial, 2));

            // Reset both the initial_balance and the current balance to the starting point
            DB::table('accounts')
                ->where('id', $row->id)
                ->update([
                    'initial_balance' => $initial,
                    'balance' => $initial // Temporarily set to initial, will be rebuilt in Step 2
                ]);
                
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Iterates through every transaction in the system in chronological order,
     * updating the account balances and setting the transaction's running balance fields.
     */
    private function recalculateRunningBalances()
    {
        // Load all transactions ordered chronologically. 
        // Note: For millions of rows, use chunkById or cursor(), but cursor is perfect here.
        $transactions = Transaction::orderBy('date', 'asc')->orderBy('time', 'asc')->orderBy('id', 'asc')->cursor();
        
        $totalTransactions = Transaction::count();
        $bar = $this->output->createProgressBar($totalTransactions);
        $bar->start();

        // Keep account balances in memory to avoid constant DB reads
        $accounts = Account::all()->keyBy('id');

        foreach ($transactions as $tx) {
            if ($tx->type === 'transfer') {
                $fromAccount = $accounts->get($tx->from_account_id);
                $toAccount = $accounts->get($tx->to_account_id);
                
                if ($fromAccount) {
                    $fromAccount->balance -= $tx->amount;
                    $tx->from_account_running_balance = $fromAccount->balance;
                }
                
                if ($toAccount) {
                    $toAccount->balance += $tx->amount;
                    $tx->to_account_running_balance = $toAccount->balance;
                }
            } else {
                $account = $accounts->get($tx->account_id);
                if ($account) {
                    if ($tx->type === 'credit') {
                        $account->balance += $tx->amount;
                    } else if ($tx->type === 'debit') {
                        $account->balance -= $tx->amount;
                    }
                    $tx->running_balance = $account->balance;
                }
            }
            
            // Save transaction silently (without updating updated_at)
            $tx->timestamps = false;
            $tx->save();

            $bar->advance();
        }

        // Finally, save the completely rebuilt balances back to the database
        foreach ($accounts as $account) {
            $account->timestamps = false;
            $account->save();
        }

        $bar->finish();
        $this->newLine(2);
    }
}
