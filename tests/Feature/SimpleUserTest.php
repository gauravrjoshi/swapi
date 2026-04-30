<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_with_fcm_token()
    {
        $user = User::create([
            'name' => 'Simple Test',
            'email' => 'simple@test.com',
            'password' => 'password',
            'fcm_token' => 'simple_token',
        ]);

        $this->assertEquals('simple_token', $user->fcm_token);
        $this->assertDatabaseHas('users', [
            'email' => 'simple@test.com',
            'fcm_token' => 'simple_token',
        ]);
    }
}
