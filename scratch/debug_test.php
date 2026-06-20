<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PersonalSubscription;
use App\Models\UserNotificationSetting;
use App\Models\DatabaseNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

// Clean tables
User::query()->delete();
PersonalSubscription::query()->delete();
UserNotificationSetting::query()->delete();
DatabaseNotification::query()->delete();

$user = User::factory()->create();

// Create active subscription due tomorrow
$tomorrow = Carbon::now('Asia/Kolkata')->addDay()->toDateString();
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

// Freeze time to 9:00 AM user time
$frozenTime = Carbon::now('Asia/Kolkata')->hour(9)->minute(0)->second(0);
Carbon::setTestNow($frozenTime);

echo "Calling notifications:dispatch-scheduled...\n";
Artisan::call('notifications:dispatch-scheduled');

$notifications = DatabaseNotification::all();
echo "Notifications in DB: " . $notifications->count() . "\n";
foreach ($notifications as $n) {
    echo "Notification: Title='{$n->title}', Body='{$n->body}', Type='{$n->type}'\n";
}

Carbon::setTestNow(); // Reset
