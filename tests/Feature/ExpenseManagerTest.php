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
        $this->service = app(TransactionService::class);
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

    public function test_cross_user_transfer_creates_linked_debit_and_credit_transactions(): void
    {
        $userA = User::factory()->create(['name' => 'User A']);
        $userB = User::factory()->create(['name' => 'User B']);

        $accountA = Account::create(['name' => 'Savings A', 'balance' => 1000, 'user_id' => $userA->id]);
        $accountB = Account::create(['name' => 'Savings B', 'balance' => 500, 'user_id' => $userB->id]);

        $primaryTx = $this->service->createTransaction([
            'user_id' => $userA->id,
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 300,
            'type' => 'transfer',
            'description' => 'Rent Payment'
        ]);

        // Assert balances are updated
        $this->assertEquals(700, $accountA->fresh()->balance);
        $this->assertEquals(800, $accountB->fresh()->balance);

        // Assert that the returned transaction is a debit for User A
        $this->assertEquals($userA->id, $primaryTx->user_id);
        $this->assertEquals('debit', $primaryTx->type);
        $this->assertEquals($accountA->id, $primaryTx->account_id);
        $this->assertNotNull($primaryTx->ref_no);

        // Assert that a credit transaction was created for User B with same ref_no
        $this->assertDatabaseHas('transactions', [
            'user_id' => $userB->id,
            'type' => 'credit',
            'account_id' => $accountB->id,
            'from_account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'amount' => 300,
            'ref_no' => $primaryTx->ref_no,
        ]);

        // Test Update
        $this->service->updateTransaction($primaryTx, [
            'amount' => 400,
            'description' => 'Updated Rent Payment'
        ]);

        $this->assertEquals(600, $accountA->fresh()->balance);
        $this->assertEquals(900, $accountB->fresh()->balance);

        // Assert updated sibling
        $this->assertDatabaseHas('transactions', [
            'user_id' => $userB->id,
            'type' => 'credit',
            'account_id' => $accountB->id,
            'amount' => 400,
            'ref_no' => $primaryTx->ref_no,
        ]);

        // Test Delete
        $this->service->deleteTransaction($primaryTx);

        // Assert balances are restored
        $this->assertEquals(1000, $accountA->fresh()->balance);
        $this->assertEquals(500, $accountB->fresh()->balance);

        // Assert both transactions are deleted
        $this->assertDatabaseMissing('transactions', [
            'ref_no' => $primaryTx->ref_no
        ]);
    }
}
