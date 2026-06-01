<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'sendResetOtp']);
    Route::post('auth/reset-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'resetPassword']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [UserController::class, 'me']);
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index']);
        Route::get('family', [\App\Http\Controllers\Api\V1\FamilyController::class, 'index']);

        // Admin Routes
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('members', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'index']);
            Route::post('members', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'store']);
            Route::put('members/{id}', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'update']);
            Route::delete('members/{id}', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'destroy']);
        });

        // Task Routes
        Route::apiResource('tasks', \App\Http\Controllers\Api\V1\TaskController::class);

        // Transaction Routes
        Route::post('transactions/import', [\App\Http\Controllers\Api\V1\TransactionController::class, 'import']);
        Route::apiResource('transactions', \App\Http\Controllers\Api\V1\TransactionController::class);

        // Tag Routes
        Route::apiResource('tags', \App\Http\Controllers\Api\V1\TagController::class);

        // Account Routes
        Route::apiResource('accounts', \App\Http\Controllers\Api\V1\AccountController::class);

        // Task Reminder Routes (Nested)
        Route::get('tasks/{task}/reminders', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'index']);
        Route::post('tasks/{task}/reminders', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'store']);
        Route::put('tasks/{task}/reminders/{reminder}', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'update']);
        Route::delete('tasks/{task}/reminders/{reminder}', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'destroy']);

        // Notification Routes
        Route::post('fcm-token', [\App\Http\Controllers\Api\V1\NotificationController::class, 'updateFcmToken']);
        Route::post('notifications/send', [\App\Http\Controllers\Api\V1\NotificationController::class, 'sendNotification']);
    });
});


// Test
Route::get('test', function () {
    Log::channel('slack')->info('Test');
    return response()->json([
        'success' => true,
        'message' => 'Test',
    ]);
});
