<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthFcmTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_fcm_token()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'fcm_token' => 'test_fcm_token_register',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $dbUser = \App\Models\User::where('email', 'test@example.com')->first();
        dump('FCM Token in DB: ' . $dbUser->fcm_token);
        $this->assertEquals('test_fcm_token_register', $dbUser->fcm_token, 'FCM Token was not saved in register');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'fcm_token' => 'test_fcm_token_register',
        ]);
    }

    public function test_user_can_login_and_update_fcm_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'fcm_token' => 'old_fcm_token',
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
            'fcm_token' => 'new_fcm_token_login',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => 'new_fcm_token_login',
        ]);
    }

    public function test_login_without_fcm_token_does_not_clear_existing_token()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'fcm_token' => 'existing_fcm_token',
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
            // fcm_token is missing
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => 'existing_fcm_token',
        ]);
    }
    public function test_auth_service_register_directly()
    {
        $service = app(\App\Services\AuthService::class);
        $data = [
            'name' => 'Direct Test',
            'email' => 'direct@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'fcm_token' => 'direct_token',
        ];

        $result = $service->register($data);

        $this->assertEquals('direct_token', $result['user']->fcm_token);
        $this->assertDatabaseHas('users', [
            'email' => 'direct@test.com',
            'fcm_token' => 'direct_token',
        ]);
    }
}
