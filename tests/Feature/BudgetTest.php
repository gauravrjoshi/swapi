<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_budgets_with_spent_amount()
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Cash',
            'balance' => 5000,
            'user_id' => $user->id,
            'account_type' => 'cash',
        ]);

        $budget1 = Budget::create([
            'user_id' => $user->id,
            'tag_id' => -1,
            'tag' => 'Food & Drinks',
            'amount' => 1000,
        ]);

        $budget2 = Budget::create([
            'user_id' => $user->id,
            'tag_id' => null,
            'tag' => 'Entertainment',
            'amount' => 2000,
        ]);

        // Create a debit transaction in the current month for Food
        Transaction::create([
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Lunch',
            'account' => 'Cash',
            'amount' => 250,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'user_id' => $user->id,
            'type' => 'debit',
            'account_id' => $account->id,
        ]);

        // Create a credit transaction for Food (should be ignored)
        Transaction::create([
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Refund',
            'account' => 'Cash',
            'amount' => 50,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'user_id' => $user->id,
            'type' => 'credit',
            'account_id' => $account->id,
        ]);

        // Create a transaction from a different month (should be ignored)
        Transaction::create([
            'date' => now()->subMonth()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Past Lunch',
            'account' => 'Cash',
            'amount' => 300,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'user_id' => $user->id,
            'type' => 'debit',
            'account_id' => $account->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/budgets');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonFragment([
                'tag' => 'Food & Drinks',
                'amount' => '1000.00',
                'spent' => 250,
            ])
            ->assertJsonFragment([
                'tag' => 'Entertainment',
                'amount' => '2000.00',
                'spent' => 0,
            ]);
    }

    public function test_can_upsert_budget_successfully()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        // 1. Create a new budget
        $response = $this->postJson('/api/v1/budgets', [
            'tag' => 'Grocery',
            'tag_id' => -3,
            'amount' => 1500,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'tag' => 'Grocery',
                'tag_id' => -3,
                'amount' => '1500.00',
                'spent' => 0,
            ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'tag' => 'Grocery',
            'amount' => 1500,
        ]);

        // 2. Update the existing budget limit (upsert)
        $response2 = $this->postJson('/api/v1/budgets', [
            'tag' => 'Grocery',
            'tag_id' => -3,
            'amount' => 2000,
        ]);

        $response2->assertStatus(201)
            ->assertJsonFragment([
                'tag' => 'Grocery',
                'tag_id' => -3,
                'amount' => '2000.00',
            ]);

        $this->assertEquals(1, Budget::count());
        $this->assertEquals(2000, Budget::first()->amount);
    }

    public function test_can_delete_budget()
    {
        $user = User::factory()->create();
        $budget = Budget::create([
            'user_id' => $user->id,
            'tag' => 'Grocery',
            'amount' => 1000,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/budgets/{$budget->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_crossing_80_percent_threshold_triggers_notification()
    {
        $user = User::factory()->create(['fcm_token' => 'mock_token']);
        $account = Account::create([
            'name' => 'Cash',
            'balance' => 5000,
            'user_id' => $user->id,
            'account_type' => 'cash',
        ]);

        $budget = Budget::create([
            'user_id' => $user->id,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'amount' => 1000,
        ]);

        // Mock NotificationService
        $notificationServiceMock = $this->mock(NotificationService::class);
        $notificationServiceMock->shouldReceive('broadcast')->once(); // Standard transaction alert broadcast
        $notificationServiceMock->shouldReceive('sendToUser')
            ->once()
            ->withArgs(function ($recipient, $title, $body, $data) use ($user) {
                return $recipient->id === $user->id &&
                    str_contains($title, 'Budget Warning') &&
                    str_contains($body, 'spent 80%') &&
                    $data['type'] === 'budget_alert';
            })
            ->andReturn(true);

        Sanctum::actingAs($user, ['*']);

        // This transaction will make total spending ₹850, which is 85% of limit (₹1000)
        $this->postJson('/api/v1/transactions', [
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Lunch',
            'account_id' => $account->id,
            'amount' => 850,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'type' => 'debit',
        ]);
    }

    public function test_crossing_100_percent_threshold_triggers_notification()
    {
        $user = User::factory()->create(['fcm_token' => 'mock_token']);
        $account = Account::create([
            'name' => 'Cash',
            'balance' => 5000,
            'user_id' => $user->id,
            'account_type' => 'cash',
        ]);

        $budget = Budget::create([
            'user_id' => $user->id,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'amount' => 1000,
        ]);

        // Create transaction of 500 (spent is 50% - no budget alerts yet)
        Transaction::create([
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Lunch 1',
            'account' => 'Cash',
            'amount' => 500,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'user_id' => $user->id,
            'type' => 'debit',
            'account_id' => $account->id,
        ]);

        // Mock NotificationService
        $notificationServiceMock = $this->mock(NotificationService::class);
        $notificationServiceMock->shouldReceive('broadcast')->once(); // Transaction alert
        $notificationServiceMock->shouldReceive('sendToUser')
            ->once()
            ->withArgs(function ($recipient, $title, $body, $data) use ($user) {
                return $recipient->id === $user->id &&
                    str_contains($title, 'Budget Exceeded') &&
                    str_contains($body, 'exceeded') &&
                    $data['type'] === 'budget_alert';
            })
            ->andReturn(true);

        Sanctum::actingAs($user, ['*']);

        // Add 600 more. Total becomes 1100 (110% of limit). Crosses 100% threshold.
        $this->postJson('/api/v1/transactions', [
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Lunch 2',
            'account_id' => $account->id,
            'amount' => 600,
            'tag' => 'Food & Drinks',
            'tag_id' => -1,
            'type' => 'debit',
        ]);
    }
}
