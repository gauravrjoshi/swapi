<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
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
            $data['date'] = $data['date'] ?? now()->toDateString();
            $data['time'] = $data['time'] ?? now()->toTimeString();
            $data['transaction_details'] = $data['transaction_details'] ?? ($data['description'] ?? 'Transaction');
            
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

            return $transaction;
        });
    }

    /**
     * Update account balance.
     *
     * @param int $accountId
     * @param float $amount
     * @return void
     */
    protected function updateBalance(int $accountId, float $amount): void
    {
        $account = Account::findOrFail($accountId);
        $account->balance += $amount;
        $account->save();
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
            $data['date'] = $data['date'] ?? now()->toDateString();
            $data['time'] = $data['time'] ?? now()->toTimeString();
            $data['transaction_details'] = $data['transaction_details'] ?? ($data['description'] ?? 'Transaction');
            
            if (isset($data['account_id'])) {
                $data['account'] = Account::find($data['account_id'])?->name ?? 'Unknown';
            } elseif (isset($data['from_account_id'])) {
                $data['account'] = Account::find($data['from_account_id'])?->name ?? 'Unknown';
            }

            $transaction->update($data);

            // 3. Apply new impact
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
}
