<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_transactions()
    {
        $user = User::factory()->create();
        Transaction::factory()->count(3)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/v1/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page']);
    }

    public function test_can_create_transaction()
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'SBI',
            'balance' => 1000,
            'user_id' => $user->id
        ]);

        $data = [
            'date' => '2023-10-27',
            'time' => '10:00:00',
            'transaction_details' => 'Grocery',
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => 500.00,
            'remarks' => 'Weekly grocery',
            'tag' => 'food,essential',
            'comment' => 'Bought from local store'
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/v1/transactions', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['transaction_details' => 'Grocery']);

        $this->assertDatabaseHas('transactions', [
            'transaction_details' => 'Grocery',
            'amount' => 500.00,
            'comment' => 'Bought from local store'
        ]);
    }

    public function test_can_show_transaction()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $transaction->id]);
    }

    public function test_can_update_transaction()
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'SBI',
            'balance' => 1000,
            'user_id' => $user->id
        ]);
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'account_id' => $account->id
        ]);

        $data = [
            'date' => '2023-10-28',
            'time' => '11:00:00',
            'transaction_details' => 'Updated Details',
            'account_id' => $account->id,
            'type' => 'debit',
            'amount' => 1000.00,
            'tag' => 'updated',
            'comment' => 'Updated comment'
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson("/api/v1/transactions/{$transaction->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['transaction_details' => 'Updated Details']);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'transaction_details' => 'Updated Details',
            'comment' => 'Updated comment'
        ]);
    }

    public function test_can_delete_transaction()
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->deleteJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_can_import_transactions()
    {
        \Maatwebsite\Excel\Facades\Excel::fake();
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/v1/transactions/import', [
            'file' => \Illuminate\Http\UploadedFile::fake()->create('transactions.xlsx')
        ]);

        $response->assertStatus(200);

        \Maatwebsite\Excel\Facades\Excel::assertImported('transactions.xlsx');
    }

    public function test_cannot_access_other_users_transaction()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $user1->id]);

        Sanctum::actingAs($user2, ['*']);
        $response = $this->getJson("/api/v1/transactions/{$transaction->id}");

        $response->assertStatus(403);
    }
}
