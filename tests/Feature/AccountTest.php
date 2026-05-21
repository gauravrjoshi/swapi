<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_account_with_banking_details()
    {
        $user = User::factory()->create();

        $data = [
            'name' => 'HDFC Savings',
            'balance' => 5000.00,
            'is_savings' => true,
            'bank_name' => 'HDFC Bank',
            'account_holder_name' => 'Gaurav Joshi',
            'account_number' => '1234567890',
            'ifsc_code' => 'HDFC0001234',
            'branch_address' => 'Mumbai, India',
            'account_type' => 'savings',
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->postJson('/api/v1/accounts', $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'HDFC Savings',
                'bank_name' => 'HDFC Bank',
                'account_holder_name' => 'Gaurav Joshi',
                'account_number' => '1234567890',
                'ifsc_code' => 'HDFC0001234',
                'branch_address' => 'Mumbai, India',
                'account_type' => 'savings',
            ]);

        $this->assertDatabaseHas('accounts', [
            'name' => 'HDFC Savings',
            'bank_name' => 'HDFC Bank',
            'account_number' => '1234567890',
        ]);
    }

    public function test_can_update_account_with_banking_details()
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'SBI General',
            'balance' => 1000.00,
            'user_id' => $user->id,
            'is_savings' => false,
            'account_type' => 'general',
        ]);

        $data = [
            'name' => 'SBI Updated',
            'bank_name' => 'State Bank of India',
            'account_holder_name' => 'Gaurav R. Joshi',
            'account_number' => '9876543210',
            'ifsc_code' => 'SBIN0004321',
            'branch_address' => 'Pune, India',
            'account_type' => 'savings',
        ];

        Sanctum::actingAs($user, ['*']);
        $response = $this->putJson("/api/v1/accounts/{$account->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'SBI Updated',
                'bank_name' => 'State Bank of India',
                'account_holder_name' => 'Gaurav R. Joshi',
                'account_number' => '9876543210',
                'ifsc_code' => 'SBIN0004321',
                'branch_address' => 'Pune, India',
                'account_type' => 'savings',
            ]);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'SBI Updated',
            'bank_name' => 'State Bank of India',
            'account_number' => '9876543210',
        ]);
    }
}
