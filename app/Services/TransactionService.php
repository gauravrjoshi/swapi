<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    protected $accountService;
    protected $notificationService;

    public function __construct(AccountService $accountService, NotificationService $notificationService)
    {
        $this->accountService = $accountService;
        $this->notificationService = $notificationService;
    }
    /**
     * Create a new transaction and update account balances.
     *
     * @param array $data
     * @return Transaction
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Fill defaults if missing
            $data['user_id'] = $data['user_id'] ?? Auth::id();
            $data['date'] = $data['date'] ?? now()->toDateString();
            $data['time'] = $data['time'] ?? now()->toTimeString();
            $data['transaction_details'] = $data['transaction_details'] ?? ($data['description'] ?? 'Transaction');

            if (empty($data['account_id']) && isset($data['account'])) {
                $data['account_id'] = $this->accountService->findByName($data['account'], $data['user_id'])?->id;
            }

            if (isset($data['type']) && $data['type'] === 'transfer') {
                if (empty($data['from_account_id']) && isset($data['from_account'])) {
                    $data['from_account_id'] = $this->accountService->findByName($data['from_account'], $data['user_id'])?->id;
                }
                if (empty($data['to_account_id']) && isset($data['to_account'])) {
                    $data['to_account_id'] = $this->accountService->findByName($data['to_account'], $data['user_id'])?->id;
                }

                // If account_id is not set but from_account_id is, sync them
                if (empty($data['account_id']) && !empty($data['from_account_id'])) {
                    $data['account_id'] = $data['from_account_id'];
                }
            }

            if (!isset($data['account'])) {
                if (isset($data['account_id'])) {
                    $data['account'] = Account::find($data['account_id'])?->name ?? 'Unknown';
                } elseif (isset($data['from_account_id'])) {
                    $data['account'] = Account::find($data['from_account_id'])?->name ?? 'Unknown';
                } else {
                    $data['account'] = 'Generic';
                }
            }

            $transaction = new Transaction($data);
            $transaction->save();

            $type = $data['type'];
            $amount = $data['amount'];

            switch ($type) {
                case 'credit':
                    $this->updateBalance($data['account_id'], $amount);
                    break;
                case 'debit':
                    $this->updateBalance($data['account_id'], -$amount);
                    break;
                case 'transfer':
                    $this->updateBalance($data['from_account_id'], -$amount);
                    $this->updateBalance($data['to_account_id'], $amount);
                    break;
            }

            // Send Push Notification
            $this->sendTransactionNotification($transaction);

            return $transaction;
        });
    }

    /**
     * Update account balance.
     *
     * @param int|null $accountId
     * @param float $amount
     * @return void
     */
    protected function updateBalance(?int $accountId, float $amount): void
    {
        if (!$accountId) {
            return;
        }

        $account = Account::find($accountId);
        if ($account) {
            // For Savings accounts: Positive amount increases balance (Credit), Negative decreases it (Debit).
            // For Liability accounts: Positive amount (Repayment/Credit) DECREASES the debt.
            //                        Negative amount (Borrowing/Debit) INCREASES the debt.
            if ($account->account_type === 'liability') {
                $account->balance -= $amount;
            } else {
                $account->balance += $amount;
            }
            $account->save();
        }
    }

    /**
     * Delete a transaction and reverse its impact on account balances.
     *
     * @param Transaction $transaction
     * @return void
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->reverseBalanceImpact($transaction);
            $this->sendTransactionNotification($transaction, 'delete');
            $transaction->delete();
        });
    }

    /**
     * Update an existing transaction.
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // 1. Reverse old impact
            $this->reverseBalanceImpact($transaction);

            // 2. Apply new data
            $data['user_id'] = $data['user_id'] ?? Auth::id();
            $data['date'] = $data['date'] ?? now()->toDateString();
            $data['time'] = $data['time'] ?? now()->toTimeString();
            $data['transaction_details'] = $data['transaction_details'] ?? ($data['description'] ?? 'Transaction');

            if (empty($data['account_id']) && isset($data['account'])) {
                $data['account_id'] = $this->accountService->findByName($data['account'], $data['user_id'])?->id;
            }

            if (isset($data['type']) && $data['type'] === 'transfer') {
                if (empty($data['from_account_id']) && isset($data['from_account'])) {
                    $data['from_account_id'] = $this->accountService->findByName($data['from_account'], $data['user_id'])?->id;
                }
                if (empty($data['to_account_id']) && isset($data['to_account'])) {
                    $data['to_account_id'] = $this->accountService->findByName($data['to_account'], $data['user_id'])?->id;
                }

                // If account_id is not set but from_account_id is, sync them
                if (empty($data['account_id']) && !empty($data['from_account_id'])) {
                    $data['account_id'] = $data['from_account_id'];
                }
            }

            if (isset($data['account_id'])) {
                $data['account'] = Account::find($data['account_id'])?->name ?? 'Unknown';
            } elseif (isset($data['from_account_id'])) {
                $data['account'] = Account::find($data['from_account_id'])?->name ?? 'Unknown';
            }

            $transaction->update($data);

            // 3. Apply new impact
            $type = $data['type'] ?? $transaction->type;
            $amount = $data['amount'] ?? $transaction->amount;
            $accountId = $data['account_id'] ?? $transaction->account_id;
            $fromAccountId = $data['from_account_id'] ?? $transaction->from_account_id;
            $toAccountId = $data['to_account_id'] ?? $transaction->to_account_id;

            switch ($type) {
                case 'credit':
                    $this->updateBalance($accountId, $amount);
                    break;
                case 'debit':
                    $this->updateBalance($accountId, -$amount);
                    break;
                case 'transfer':
                    $this->updateBalance($fromAccountId, -$amount);
                    $this->updateBalance($toAccountId, $amount);
                    break;
            }

            $this->sendTransactionNotification($transaction, 'update');

            return $transaction;
        });
    }

    /**
     * Reverse the impact of a transaction on account balances.
     *
     * @param Transaction $transaction
     * @return void
     */
    protected function reverseBalanceImpact(Transaction $transaction): void
    {
        $type = $transaction->type;
        $amount = $transaction->amount;

        switch ($type) {
            case 'credit':
                $this->updateBalance($transaction->account_id, -$amount);
                break;
            case 'debit':
                $this->updateBalance($transaction->account_id, $amount);
                break;
            case 'transfer':
                $this->updateBalance($transaction->from_account_id, $amount);
                $this->updateBalance($transaction->to_account_id, -$amount);
                break;
        }
    }


    public function getTransactions(int $userId, array $filters = [], int $perPage = 10)
    {
        $query = Transaction::with(['mainAccount', 'fromAccount', 'toAccount', 'user'])
            // ->where('user_id', $userId)
            ->latest();

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhere('transaction_details', 'like', '%' . $search . '%')
                    ->orWhere('tag', 'like', '%' . $search . '%');
            });
        }

        // if (!empty($filters['user_id'])) {
        //     $query->where('user_id', $filters['user_id']);
        // }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }

        return $query->paginate($perPage);
    }
    /**
     * Send a push notification for a transaction.
     *
     * @param Transaction $transaction
     * @param string $action 'create', 'update', or 'delete'
     * @return void
     */
    protected function sendTransactionNotification(Transaction $transaction, string $action = 'create'): void
    {
        $owner = $transaction->user;
        if (!$owner) {
            return;
        }

        $type = ucfirst($transaction->type);
        $amount = number_format((float) $transaction->amount, 2);
        $currency = "₹";
        $ownerName = $owner->name ?? 'User';

        $title = "Transaction Alert: " . ucfirst($action);
        $body = "";

        if ($action === 'create') {
            switch ($transaction->type) {
                case 'credit':
                    $body = "Amount of {$currency}{$amount} has been credited to {$ownerName}'s account.";
                    break;
                case 'debit':
                    $body = "Amount of {$currency}{$amount} has been debited from {$ownerName}'s account.";
                    break;
                case 'transfer':
                    $body = "{$ownerName} transferred {$currency}{$amount} from {$transaction->account} to {$transaction->other_transaction_details}.";
                    break;
                default:
                    $body = "A new transaction of {$currency}{$amount} has been recorded for {$ownerName}.";
            }
        } elseif ($action === 'update') {
            $body = "A transaction of {$currency}{$amount} for {$ownerName} has been updated.";
        } elseif ($action === 'delete') {
            $body = "A transaction of {$currency}{$amount} for {$ownerName} has been deleted.";
        }

        $this->notificationService->broadcast($title, $body, [
            'transaction_id' => (string) $transaction->id,
            'type' => 'transaction',
            'action' => $action,
        ], [$owner->id]);
    }
}
