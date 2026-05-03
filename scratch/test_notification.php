<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NotificationService;
use App\Models\User;

try {
    echo "Attempting to resolve NotificationService from container..." . PHP_EOL;
    $service = $app->make(NotificationService::class);
    echo "NotificationService resolved successfully!" . PHP_EOL;

    // Test with a dummy user
    $user = new User();
    $user->id = 999;
    $user->fcm_token = 'es1m-C76S0iVw55jaF1nKI:APA91bFR3eKCcAQ3D1Ovn6t_pbAs3Z_XgmKIBTWtWOJ4FMLhgqrisfUI82lydkWNGjFZASwRcr_fcfsK5yeNwH9RM7JaGgR8dozOPvkosLpREVC4tbWy9CI';

    echo "Attempting to send a dummy notification..." . PHP_EOL;
    $result = $service->sendToUser($user, 'Test Title', 'Test Body');
    echo "Result: " . ($result ? "Success" : "Failure") . PHP_EOL;

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
