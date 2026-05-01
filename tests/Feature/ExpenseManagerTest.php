<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagerTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransactionService();
    }

    public function test_credit_increases_account_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000,
            'user_id' => $user->id
        ]);

        $this->service->createTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 500,
            'type' => 'credit',
            'description' => 'Salary'
        ]);

        $this->assertEquals(1500, $account->fresh()->balance);
    }

    public function test_debit_decreases_account_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Test Account',
            'balance' => 1000,
            'user_id' => $user->id
        ]);

        $this->service->createTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 200,
            'type' => 'debit',
            'description' => 'Dinner'
        ]);

        $this->assertEquals(800, $account->fresh()->balance);
    }

    public function test_transfer_updates_both_account_balances(): void
    {
        $user = User::factory()->create();
        $from = Account::create(['name' => 'Bank', 'balance' => 1000, 'user_id' => $user->id]);
        $to = Account::create(['name' => 'Cash', 'balance' => 500, 'user_id' => $user->id]);

        $this->service->createTransaction([
            'user_id' => $user->id,
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 300,
            'type' => 'transfer',
            'description' => 'ATM Withdrawal'
        ]);

        $this->assertEquals(700, $from->fresh()->balance);
        $this->assertEquals(800, $to->fresh()->balance);
    }

    public function test_transaction_is_recorded_in_database(): void
    {
        $user = User::factory()->create();
        $account = Account::create(['name' => 'Test', 'balance' => 1000, 'user_id' => $user->id]);

        $this->service->createTransaction([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100,
            'type' => 'debit',
            'description' => 'Coffee'
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount' => 100,
            'type' => 'debit'
        ]);
    }
}
