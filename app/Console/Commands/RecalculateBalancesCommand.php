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
    protected $description = 'Recalculates all account balances and transaction running balances based on existing initial balances.';

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
                // Step 1: Initialize current balances to initial balances
                $this->info("Step 1: Initializing balances from existing initial_balance values...");
                $this->initializeBalancesFromInitial();

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
     * Resets the current `balance` of all accounts to their `initial_balance`
     * to prepare for chronological forward calculation.
     */
    private function initializeBalancesFromInitial()
    {
        DB::table('accounts')->update([
            'balance' => DB::raw('initial_balance')
        ]);

        $this->info("All account current balances reset to their initial_balance.");
    }

    /**
     * Iterates through every transaction in the system in chronological order,
     * updating the account balances and setting the transaction's running balance fields.
     */
    private function recalculateRunningBalances()
    {
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
                    if ($tx->type === 'credit' || $tx->type === 'income') {
                        $account->balance += $tx->amount;
                    } else if ($tx->type === 'debit' || $tx->type === 'expense') {
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
