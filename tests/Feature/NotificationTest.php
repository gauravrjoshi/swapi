<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Kreait\Firebase\Contract\Messaging;
use Mockery;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_can_update_fcm_token()
    {
        $user = User::factory()->create();
        $token = 'test_fcm_token_123';

        $response = $this->actingAs($user)->postJson('/api/v1/fcm-token', [
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'FCM token updated successfully.']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fcm_token' => $token,
        ]);
    }

    public function test_send_notification_successfully()
    {
        $user = User::factory()->create(['fcm_token' => 'recipient_token_123']);
        $sender = User::factory()->create();

        $this->mock(Messaging::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $response = $this->actingAs($sender)->postJson('/api/v1/notifications/send', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Notification sent successfully.']);
    }

    public function test_send_notification_fails_if_user_has_no_token()
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $sender = User::factory()->create();

        $response = $this->actingAs($sender)->postJson('/api/v1/notifications/send', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'User does not have an FCM token.']);
    }
}
