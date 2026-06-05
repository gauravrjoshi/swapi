<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Collection;

class AccountService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all accounts for a user.
     *
     * @param int|null $userId
     * @param bool $workspaceWide
     * @return Collection
     */
    public function getAccounts(?int $userId = null, bool $workspaceWide = false): Collection
    {
        $query = Account::with('user')
            ->orderBy('account_type', 'asc')
            ->orderBy('name', 'asc');

        if (!$workspaceWide) {
            $userId = $userId ?? auth()->id();
            if ($userId) {
                $query->where('user_id', $userId);
            }
        }

        return $query->get();
    }

    /**
     * Create a new account.
     *
     * @param array $data
     * @return Account
     */
    public function createAccount(array $data): Account
    {
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        if (isset($data['account_type'])) {
            $data['is_savings'] = ($data['account_type'] === 'savings');
        } elseif (isset($data['is_savings'])) {
            $data['account_type'] = $data['is_savings'] ? 'savings' : 'general';
        } else {
            $data['account_type'] = 'general';
            $data['is_savings'] = false;
        }

        if (!isset($data['balance']) && isset($data['initial_balance'])) {
            $data['balance'] = $data['initial_balance'];
        }

        if (isset($data['balance']) && !isset($data['initial_balance'])) {
            $data['initial_balance'] = $data['balance'];
        }

        $account = Account::create($data);
        $this->sendAccountNotification($account, 'created');
        return $account;
    }

    /**
     * Update an account.
     *
     * @param Account $account
     * @param array $data
     * @return Account
     */
    public function updateAccount(Account $account, array $data): Account
    {
        if (isset($data['account_type'])) {
            $data['is_savings'] = ($data['account_type'] === 'savings');
        } elseif (isset($data['is_savings'])) {
            $data['account_type'] = $data['is_savings'] ? 'savings' : 'general';
        }

        $account->update($data);
        $this->sendAccountNotification($account, 'updated');
        return $account;
    }

    /**
     * Delete an account.
     *
     * @param Account $account
     * @return bool
     */
    public function deleteAccount(Account $account): bool
    {
        $this->sendAccountNotification($account, 'deleted');
        return $account->delete();
    }

    /**
     * Find an account by name for a specific user.
     *
     * @param string $name
     * @param int $userId
     * @return Account|null
     */
    public function findByName(string $name, int $userId): ?Account
    {
        return Account::where('name', 'like', trim($name))
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Send a notification for account actions.
     *
     * @param Account $account
     * @param string $action
     * @return void
     */
    protected function sendAccountNotification(Account $account, string $action): void
    {
        $owner = $account->user;
        $ownerName = $owner->name ?? 'User';

        $title = "Account Alert: " . ucfirst($action);
        $body = "Account '{$account->name}' has been {$action} for {$ownerName}.";

        $this->notificationService->broadcast($title, $body, [
            'account_id' => (string) $account->id,
            'type' => 'account',
            'action' => $action
        ], $owner ? [$owner->id] : []);
    }
}
