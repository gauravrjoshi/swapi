<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DraftTransaction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DraftTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_can_list_draft_transactions()
    {
        $user = User::factory()->create();
        DraftTransaction::create([
            'uuid' => 'draft-1',
            'user_id' => $user->id,
            'amount' => 100.00,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->getJson('/api/v1/draft-transactions');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['uuid' => 'draft-1']);
    }

    public function test_can_create_draft_transaction_with_image()
    {
        $user = User::factory()->create();
        $data = [
            'uuid' => 'test-uuid-123',
            'amount' => 250.50,
            'date' => '2026-06-08',
            'time' => '12:30:00',
            'transaction_details' => 'Coffee Day',
            'type' => 'debit',
            'image' => UploadedFile::fake()->image('receipt.jpg')
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/v1/draft-transactions', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['uuid' => 'test-uuid-123']);

        $this->assertDatabaseHas('draft_transactions', [
            'uuid' => 'test-uuid-123',
            'amount' => 250.50,
            'transaction_details' => 'Coffee Day'
        ]);

        $draft = DraftTransaction::where('uuid', 'test-uuid-123')->first();
        $this->assertNotNull($draft->getFirstMedia('receipt'));
        $this->assertNotEmpty($draft->getFirstMediaUrl('receipt'));
    }

    public function test_can_update_draft_transaction()
    {
        $user = User::factory()->create();
        $draft = DraftTransaction::create([
            'uuid' => 'draft-2',
            'user_id' => $user->id,
            'amount' => 100.00,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson("/api/v1/draft-transactions/{$draft->id}", [
            'amount' => 150.00,
            'transaction_details' => 'Updated details'
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['amount' => '150.00']);

        $this->assertDatabaseHas('draft_transactions', [
            'id' => $draft->id,
            'amount' => 150.00,
            'transaction_details' => 'Updated details'
        ]);
    }

    public function test_can_delete_draft_transaction()
    {
        $user = User::factory()->create();
        $draft = DraftTransaction::create([
            'uuid' => 'draft-3',
            'user_id' => $user->id,
            'amount' => 100.00,
            'status' => 'pending'
        ]);

        Sanctum::actingAs($user, ['*']);
        $response = $this->deleteJson("/api/v1/draft-transactions/{$draft->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('draft_transactions', ['id' => $draft->id]);
    }

    public function test_can_confirm_draft_transaction_and_transfer_media()
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'Cash',
            'balance' => 1000,
            'user_id' => $user->id
        ]);

        $draft = DraftTransaction::create([
            'uuid' => 'draft-to-confirm',
            'user_id' => $user->id,
            'amount' => 500.00,
            'status' => 'pending'
        ]);

        // Add a media receipt to the draft
        $draft->addMedia(UploadedFile::fake()->image('receipt.jpg'))->toMediaCollection('receipt');

        $confirmData = [
            'date' => '2026-06-08',
            'time' => '14:00:00',
            'type' => 'debit',
            'amount' => 500.00,
            'account_id' => $account->id,
            'transaction_details' => 'Grocery Store',
            'comment' => 'Confirmed draft'
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson("/api/v1/draft-transactions/{$draft->id}/confirm", $confirmData);

        $response->assertStatus(201)
            ->assertJsonFragment(['amount' => '500.00']);

        // Assert draft is deleted
        $this->assertDatabaseMissing('draft_transactions', ['id' => $draft->id]);

        // Assert transaction is created
        $this->assertDatabaseHas('transactions', [
            'amount' => 500.00,
            'transaction_details' => 'Grocery Store',
            'comment' => 'Confirmed draft'
        ]);

        $transaction = Transaction::where('amount', 500.00)->first();
        $this->assertNotNull($transaction->getFirstMedia('receipt'));
        $this->assertNotEmpty($transaction->getFirstMediaUrl('receipt'));
    }
}
