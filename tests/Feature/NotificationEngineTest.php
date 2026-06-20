<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\DatabaseNotification;
use App\Models\PersonalSubscription;
use App\Models\RecurringBill;
use App\Models\UserNotificationSetting;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_notification_settings_creates_defaults()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/notification-settings');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'user_id' => $user->id,
                'timezone' => 'Asia/Kolkata',
                'enable_daily_reminder' => true,
            ]);

        $this->assertDatabaseHas('user_notification_settings', [
            'user_id' => $user->id,
            'timezone' => 'Asia/Kolkata',
        ]);
    }

    public function test_update_notification_settings()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/notification-settings', [
            'timezone' => 'America/New_York',
            'reminder_time' => '19:30:00',
            'enable_daily_reminder' => false,
            'low_balance_threshold' => 500,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'timezone' => 'America/New_York',
                'reminder_time' => '19:30:00',
                'enable_daily_reminder' => false,
                'low_balance_threshold' => '500.00',
            ]);
    }

    public function test_notifications_stored_in_history_log()
    {
        $user = User::factory()->create(['fcm_token' => 'token_123']);
        Sanctum::actingAs($user, ['*']);

        // Trigger a notification
        $service = app(NotificationService::class);
        $service->sendToUser($user, 'Test Title', 'Test Body', ['type' => 'insight']);

        // Verify it was logged in database
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
            'type' => 'insight',
        ]);

        // Get logs history via API
        $response = $this->getJson('/api/v1/notifications');
        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Test Title']);

        $notificationId = $response->json()[0]['id'];

        // Mark as read
        $response2 = $this->postJson("/api/v1/notifications/{$notificationId}/read");
        $response2->assertStatus(200);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notificationId,
            'read_at' => null,
        ]);
    }

    public function test_transaction_triggers_low_balance_alert()
    {
        $user = User::factory()->create();
        $account = Account::create([
            'name' => 'My Bank',
            'balance' => 2000,
            'user_id' => $user->id,
            'account_type' => 'savings',
        ]);

        // Enable low balance settings
        UserNotificationSetting::create([
            'user_id' => $user->id,
            'timezone' => 'Asia/Kolkata',
            'enable_low_balance_alerts' => true,
            'low_balance_threshold' => 1500.00,
        ]);

        Sanctum::actingAs($user, ['*']);

        // Spend ₹800. Balance becomes ₹1200 (below ₹1500 threshold). Triggers low balance alert.
        $response = $this->postJson('/api/v1/transactions', [
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Shopping',
            'account_id' => $account->id,
            'amount' => 800,
            'tag' => 'Shopping',
            'type' => 'debit',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'low_balance_alerts',
        ]);
    }

    public function test_scheduled_worker_daily_reminder_trigger()
    {
        $user = User::factory()->create();
        
        // Mock current local time to match reminder_time (e.g. 20:00)
        $settings = UserNotificationSetting::create([
            'user_id' => $user->id,
            'timezone' => 'Asia/Kolkata',
            'reminder_time' => '20:00:00',
            'enable_daily_reminder' => true,
        ]);

        // Freeze system time to 8:00 PM Asia/Kolkata (which is 14:30:00 UTC)
        $frozenTime = \Carbon\Carbon::create(2026, 6, 20, 20, 0, 0, 'Asia/Kolkata');
        \Carbon\Carbon::setTestNow($frozenTime);

        // Run scheduler command
        Artisan::call('notifications:dispatch-scheduled');

        // Check that daily reminder is sent (because no transaction exists today)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'daily_reminder',
        ]);

        \Carbon\Carbon::setTestNow(); // Reset time
    }

    public function test_scheduled_worker_skips_daily_reminder_if_transaction_logged()
    {
        $user = User::factory()->create();
        
        // Mock local time to match reminder_time
        $settings = UserNotificationSetting::create([
            'user_id' => $user->id,
            'timezone' => 'Asia/Kolkata',
            'reminder_time' => '20:00:00',
            'enable_daily_reminder' => true,
        ]);

        $account = Account::create([
            'name' => 'Cash',
            'balance' => 1000,
            'user_id' => $user->id,
            'account_type' => 'cash',
        ]);

        // Freeze system time to 8:00 PM Asia/Kolkata
        $frozenTime = \Carbon\Carbon::create(2026, 6, 20, 20, 0, 0, 'Asia/Kolkata');
        \Carbon\Carbon::setTestNow($frozenTime);

        // Create transaction logged today
        Transaction::create([
            'date' => $frozenTime->toDateString(),
            'time' => '12:00:00',
            'transaction_details' => 'Lunch',
            'account' => 'Cash',
            'amount' => 50,
            'tag' => 'Food',
            'user_id' => $user->id,
            'type' => 'debit',
            'account_id' => $account->id,
        ]);

        // Run scheduler command
        Artisan::call('notifications:dispatch-scheduled');

        // Daily reminder should NOT be sent
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id,
            'type' => 'daily_reminder',
        ]);

        \Carbon\Carbon::setTestNow(); // Reset time
    }

    public function test_scheduled_worker_subscription_alert()
    {
        $user = User::factory()->create();
        
        // Create active subscription due tomorrow
        $tomorrow = \Carbon\Carbon::now('Asia/Kolkata')->addDay()->toDateString();
        PersonalSubscription::create([
            'user_id' => $user->id,
            'name' => 'Netflix',
            'amount' => 649.00,
            'billing_cycle' => 'monthly',
            'next_renewal_date' => $tomorrow,
            'is_active' => true,
        ]);

        UserNotificationSetting::create([
            'user_id' => $user->id,
            'timezone' => 'Asia/Kolkata',
            'enable_subscription_reminders' => true,
        ]);

        // Freeze system time to 9:00 AM user time
        $frozenTime = \Carbon\Carbon::now('Asia/Kolkata')->hour(9)->minute(0)->second(0);
        \Carbon\Carbon::setTestNow($frozenTime);

        Artisan::call('notifications:dispatch-scheduled');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'subscription',
            'body' => 'Your Netflix subscription renews tomorrow (₹649.00).',
        ]);

        \Carbon\Carbon::setTestNow();
    }
}
