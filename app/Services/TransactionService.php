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

            $isCrossUserTransfer = false;
            $fromAccount = null;
            $toAccount = null;

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

                if (!empty($data['from_account_id']) && !empty($data['to_account_id'])) {
                    $fromAccount = Account::withTrashed()->find($data['from_account_id']);
                    $toAccount = Account::withTrashed()->find($data['to_account_id']);
                    if ($fromAccount && $toAccount && $fromAccount->user_id !== $toAccount->user_id) {
                        $isCrossUserTransfer = true;
                    }
                }
            }

            if ($isCrossUserTransfer) {
                $refNo = $data['ref_no'] ?? ('trf_' . \Illuminate\Support\Str::random(16));

                // 1. Create Sibling (Credit) Transaction for Recipient
                $siblingData = [
                    'date' => $data['date'],
                    'time' => $data['time'],
                    'type' => 'credit',
                    'account_id' => $toAccount->id,
                    'account' => $toAccount->name,
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => $data['amount'],
                    'ref_no' => $refNo,
                    'order_id' => $data['order_id'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'tag' => $data['tag'] ?? null,
                    'tag_id' => null, // resolved scoped via saving hook
                    'comment' => $data['comment'] ?? null,
                    'user_id' => $toAccount->user_id,
                    'description' => $data['description'] ?? null,
                    'transaction_details' => $data['transaction_details'] ?? ("Transfer from " . ($fromAccount->user?->name ?? 'User')),
                    'other_transaction_details' => $fromAccount->name,
                ];

                $sibling = new Transaction($siblingData);
                $sibling->save();
                $sibling->running_balance = $this->updateBalance($toAccount->id, (float) $data['amount']);
                $sibling->save();
                $this->sendTransactionNotification($sibling, 'create');

                // 2. Create Primary (Debit) Transaction for Sender
                $primaryData = [
                    'date' => $data['date'],
                    'time' => $data['time'],
                    'type' => 'debit',
                    'account_id' => $fromAccount->id,
                    'account' => $fromAccount->name,
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => $data['amount'],
                    'ref_no' => $refNo,
                    'order_id' => $data['order_id'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'tag' => $data['tag'] ?? null,
                    'tag_id' => $data['tag_id'] ?? null,
                    'comment' => $data['comment'] ?? null,
                    'user_id' => $fromAccount->user_id,
                    'description' => $data['description'] ?? null,
                    'transaction_details' => $data['transaction_details'] ?? ("Transfer to " . ($toAccount->user?->name ?? 'User')),
                    'other_transaction_details' => $toAccount->name,
                ];

                $transaction = new Transaction($primaryData);
                $transaction->save();
                $transaction->running_balance = $this->updateBalance($fromAccount->id, -(float) $data['amount']);
                $transaction->save();
                $this->sendTransactionNotification($transaction, 'create');

                return $transaction;
            }

            if (!isset($data['account'])) {
                if (isset($data['account_id'])) {
                    $data['account'] = Account::withTrashed()->find($data['account_id'])?->name ?? 'Unknown';
                } elseif (isset($data['from_account_id'])) {
                    $data['account'] = Account::withTrashed()->find($data['from_account_id'])?->name ?? 'Unknown';
                } else {
                    $data['account'] = 'Generic';
                }
            }

            $transaction = new Transaction($data);
            $transaction->save();

            $type = $data['type'];
            $amount = (float) $data['amount'];

            switch ($type) {
                case 'credit':
                    $transaction->running_balance = $this->updateBalance($data['account_id'], $amount);
                    break;
                case 'debit':
                    $transaction->running_balance = $this->updateBalance($data['account_id'], -$amount);
                    break;
                case 'transfer':
                    $transaction->from_account_running_balance = $this->updateBalance($data['from_account_id'], -$amount);
                    $transaction->to_account_running_balance = $this->updateBalance($data['to_account_id'], $amount);
                    break;
            }

            $transaction->save();

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
     * @return float|null
     */
    protected function updateBalance(?int $accountId, $amount): ?float
    {
        if (!$accountId || $amount === null) {
            return null;
        }

        $amount = (float) $amount;

        $account = Account::withTrashed()->find($accountId);
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
            return $account->balance;
        }
        return null;
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
            // Sibling check for cross-user transfer (linked by ref_no)
            if (in_array($transaction->type, ['credit', 'debit']) && $transaction->from_account_id && $transaction->to_account_id && $transaction->ref_no) {
                $siblingType = $transaction->type === 'debit' ? 'credit' : 'debit';
                $sibling = Transaction::where('ref_no', $transaction->ref_no)
                    ->where('type', $siblingType)
                    ->where('id', '!=', $transaction->id)
                    ->first();

                if ($sibling) {
                    $this->reverseBalanceImpact($sibling);
                    $this->sendTransactionNotification($sibling, 'delete');
                    $sibling->delete();
                }
            }

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
            // If the original transaction was a cross-user transfer, find and delete the sibling first to clean up old states
            if (in_array($transaction->type, ['credit', 'debit']) && $transaction->from_account_id && $transaction->to_account_id && $transaction->ref_no) {
                $siblingType = $transaction->type === 'debit' ? 'credit' : 'debit';
                $sibling = Transaction::where('ref_no', $transaction->ref_no)
                    ->where('type', $siblingType)
                    ->where('id', '!=', $transaction->id)
                    ->first();

                if ($sibling) {
                    $this->reverseBalanceImpact($sibling);
                    $this->sendTransactionNotification($sibling, 'delete');
                    $sibling->delete();
                }
            }

            // 1. Reverse old impact of main transaction
            $this->reverseBalanceImpact($transaction);

            // 2. Apply defaults & setup
            $data['user_id'] = $data['user_id'] ?? Auth::id() ?? $transaction->user_id;
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

            // Determine if the new/updated state is a cross-user transfer
            $isCrossUserTransfer = false;
            $fromAccount = null;
            $toAccount = null;

            $wasTransfer = ($transaction->type === 'transfer' || (!empty($transaction->from_account_id) && !empty($transaction->to_account_id)));
            $newType = $data['type'] ?? ($wasTransfer ? 'transfer' : $transaction->type);
            $newFromAccountId = $data['from_account_id'] ?? $transaction->from_account_id;
            $newToAccountId = $data['to_account_id'] ?? $transaction->to_account_id;

            if ($newType === 'transfer' && !empty($newFromAccountId) && !empty($newToAccountId)) {
                $fromAccount = Account::withTrashed()->find($newFromAccountId);
                $toAccount = Account::withTrashed()->find($newToAccountId);
                if ($fromAccount && $toAccount && $fromAccount->user_id !== $toAccount->user_id) {
                    $isCrossUserTransfer = true;
                }
            }

            if ($isCrossUserTransfer) {
                // Generate or reuse ref_no
                $refNo = $data['ref_no'] ?? $transaction->ref_no ?? ('trf_' . \Illuminate\Support\Str::random(16));
                $amount = (float) ($data['amount'] ?? $transaction->amount);

                // 1. Create/Recreate sibling (credit) for recipient
                $siblingData = [
                    'date' => $data['date'] ?? $transaction->date->toDateString(),
                    'time' => $data['time'] ?? $transaction->time,
                    'type' => 'credit',
                    'account_id' => $toAccount->id,
                    'account' => $toAccount->name,
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => $amount,
                    'ref_no' => $refNo,
                    'order_id' => $data['order_id'] ?? $transaction->order_id,
                    'remarks' => $data['remarks'] ?? $transaction->remarks,
                    'tag' => $data['tag'] ?? $transaction->tag,
                    'tag_id' => null, // resolved scoped
                    'comment' => $data['comment'] ?? $transaction->comment,
                    'user_id' => $toAccount->user_id,
                    'description' => $data['description'] ?? $transaction->description,
                    'transaction_details' => $data['transaction_details'] ?? ("Transfer from " . ($fromAccount->user?->name ?? 'User')),
                    'other_transaction_details' => $fromAccount->name,
                ];

                $sibling = new Transaction($siblingData);
                $sibling->save();
                $sibling->running_balance = $this->updateBalance($toAccount->id, $amount);
                $sibling->save();
                $this->sendTransactionNotification($sibling, 'create');

                // 2. Update the main transaction to be the debit (primary) for sender
                $data['type'] = 'debit';
                $data['account_id'] = $fromAccount->id;
                $data['account'] = $fromAccount->name;
                $data['from_account_id'] = $fromAccount->id;
                $data['to_account_id'] = $toAccount->id;
                $data['ref_no'] = $refNo;
                $data['transaction_details'] = $data['transaction_details'] ?? ("Transfer to " . ($toAccount->user?->name ?? 'User'));
                $data['other_transaction_details'] = $toAccount->name;

                $transaction->update($data);
                $transaction->running_balance = $this->updateBalance($fromAccount->id, -$amount);
                $transaction->from_account_running_balance = null;
                $transaction->to_account_running_balance = null;
                $transaction->save();

                $this->sendTransactionNotification($transaction, 'update');

                return $transaction;
            }

            // Normal standard update flow (for self-transfer, credit, debit)
            if (isset($data['account_id'])) {
                $data['account'] = Account::withTrashed()->find($data['account_id'])?->name ?? 'Unknown';
            } elseif (isset($data['from_account_id'])) {
                $data['account'] = Account::withTrashed()->find($data['from_account_id'])?->name ?? 'Unknown';
            }

            $transaction->update($data);

            $type = $data['type'] ?? $transaction->type;
            $amount = (float) ($data['amount'] ?? $transaction->amount);
            $accountId = $data['account_id'] ?? $transaction->account_id;
            $fromAccountId = $data['from_account_id'] ?? $transaction->from_account_id;
            $toAccountId = $data['to_account_id'] ?? $transaction->to_account_id;

            switch ($type) {
                case 'credit':
                    $transaction->running_balance = $this->updateBalance($accountId, $amount);
                    $transaction->from_account_running_balance = null;
                    $transaction->to_account_running_balance = null;
                    break;
                case 'debit':
                    $transaction->running_balance = $this->updateBalance($accountId, -$amount);
                    $transaction->from_account_running_balance = null;
                    $transaction->to_account_running_balance = null;
                    break;
                case 'transfer':
                    $transaction->from_account_running_balance = $this->updateBalance($fromAccountId, -$amount);
                    $transaction->to_account_running_balance = $this->updateBalance($toAccountId, $amount);
                    $transaction->running_balance = null;
                    break;
            }

            $transaction->save();

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
        $amount = (float) $transaction->amount;

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


    protected function applyFilters($query, int $userId, array $filters = [])
    {
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('description', 'like', '%' . $search . '%')
                    ->orWhere('transaction_details', 'like', '%' . $search . '%')
                    ->orWhere('tag', 'like', '%' . $search . '%');
            });
        }
        // if auth user is admin then he can see all transactions
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['account_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('account_id', $filters['account_id'])
                    ->orWhere('from_account_id', $filters['account_id'])
                    ->orWhere('to_account_id', $filters['account_id']);
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }

        if (!empty($filters['tag_id'])) {
            $tagId = $filters['tag_id'];
            if (is_numeric($tagId)) {
                $query->where('tag_id', $tagId);
            } else {
                $query->where('tag', $tagId);
            }
        }

        return $query;
    }

    public function getTransactions(int $userId, array $filters = [], int $perPage = 10)
    {
        $query = Transaction::with(['mainAccount', 'fromAccount', 'toAccount', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->orderBy('id', 'desc');

        $query = $this->applyFilters($query, $userId, $filters);

        return $query->paginate($perPage);
    }

    public function getTransactionSums(int $userId, array $filters = []): array
    {
        $query = Transaction::query();
        $query = $this->applyFilters($query, $userId, $filters);

        $sums = $query->groupBy('type')
            ->select('type', DB::raw('SUM(amount) as total'))
            ->get()
            ->pluck('total', 'type')
            ->toArray();

        return [
            'credit' => (float) ($sums['credit'] ?? 0.0),
            'debit' => (float) ($sums['debit'] ?? 0.0),
            'transfer' => (float) ($sums['transfer'] ?? 0.0),
        ];
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

        if ($action === 'create' || $action === 'update') {
            $this->checkBudgetThresholds($transaction);
        }
    }

    /**
     * Check budget thresholds and send FCM notifications if crossed.
     *
     * @param Transaction $transaction
     * @return void
     */
    protected function checkBudgetThresholds(Transaction $transaction): void
    {
        // Only run for debit (expense) transactions that have a category (tag)
        if ($transaction->type !== 'debit' || empty($transaction->tag)) {
            return;
        }

        $userId = $transaction->user_id;
        $tag = $transaction->tag;
        $tagId = $transaction->tag_id;

        // Find a budget for this category
        $budget = \App\Models\Budget::where('user_id', $userId)
            ->where(function ($query) use ($tag, $tagId) {
                $query->where('tag', $tag);
                if ($tagId !== null) {
                    $query->orWhere('tag_id', $tagId);
                }
            })
            ->first();

        if (!$budget) {
            return;
        }

        $limit = (float) $budget->amount;
        if ($limit <= 0) {
            return;
        }

        // Parse transaction's date to check budget for that specific month
        $txDate = \Carbon\Carbon::parse($transaction->date);
        $startOfMonth = $txDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $txDate->copy()->endOfMonth()->toDateString();

        // Calculate total spending in that month excluding this transaction
        $previousSpent = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'debit')
            ->where('id', '!=', $transaction->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where(function ($query) use ($tag, $tagId) {
                $query->where('tag', $tag);
                if ($tagId !== null) {
                    $query->orWhere('tag_id', $tagId);
                }
            })
            ->sum('amount');

        $previousSpent = (float) $previousSpent;
        $currentSpent = $previousSpent + (float) $transaction->amount;

        $user = $transaction->user ?? User::find($userId);
        if (!$user) {
            return;
        }

        $currency = "₹";
        $formattedLimit = number_format($limit, 2);
        $formattedSpent = number_format($currentSpent, 2);

        // Check 100% threshold crossover
        if ($previousSpent < $limit && $currentSpent >= $limit) {
            $title = "Budget Exceeded: {$tag}";
            $body = "You have exceeded your {$tag} budget of {$currency}{$formattedLimit} (Spent: {$currency}{$formattedSpent}).";
            $this->notificationService->sendToUser($user, $title, $body, [
                'type' => 'budget_alert',
                'budget_id' => (string) $budget->id,
                'tag' => $tag,
            ]);
        }
        // Check 80% threshold crossover
        elseif ($previousSpent < ($limit * 0.8) && $currentSpent >= ($limit * 0.8)) {
            $title = "Budget Warning: {$tag}";
            $body = "You have spent 80% of your {$tag} budget (Spent: {$currency}{$formattedSpent} of {$currency}{$formattedLimit}).";
            $this->notificationService->sendToUser($user, $title, $body, [
                'type' => 'budget_alert',
                'budget_id' => (string) $budget->id,
                'tag' => $tag,
            ]);
        }
    }
}
