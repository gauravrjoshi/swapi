<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AppVersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // App Version Check (public — no auth required)
    Route::get('app-version', [AppVersionController::class, 'index']);

    // Auth Routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'sendResetOtp']);
    Route::post('auth/reset-password', [\App\Http\Controllers\Api\V1\ForgotPasswordController::class, 'resetPassword']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [UserController::class, 'me']);
        Route::put('profile', [UserController::class, 'update']);
        Route::get('dashboard', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index']);
        Route::get('family', [\App\Http\Controllers\Api\V1\FamilyController::class, 'index']);

        // Admin Routes
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('members', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'index']);
            Route::post('members', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'store']);
            Route::put('members/{id}', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'update']);
            Route::delete('members/{id}', [\App\Http\Controllers\Api\V1\Admin\MemberController::class, 'destroy']);

            // App Version Settings (admin-only update)
            Route::put('app-version', [AppVersionController::class, 'update']);

            // Device Info Analytics (admin-only read)
            Route::get('device-info', [\App\Http\Controllers\Api\V1\Admin\DeviceInfoController::class, 'index']);
        });
        // Support Ticket Admin Routes
        Route::get('admin/support-tickets', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'index']);
        Route::put('admin/support-tickets/{id}/status', [\App\Http\Controllers\Api\V1\Admin\SupportTicketController::class, 'updateStatus']);

        // Support Ticket User Routes
        Route::apiResource('support-tickets', \App\Http\Controllers\Api\V1\SupportTicketController::class)->only(['index', 'store', 'show']);

        // Task Routes
        Route::apiResource('tasks', \App\Http\Controllers\Api\V1\TaskController::class);

        // Transaction Routes
        Route::post('transactions/import', [\App\Http\Controllers\Api\V1\TransactionController::class, 'import']);
        Route::get('transactions/{id}/receipt', [\App\Http\Controllers\Api\V1\TransactionController::class, 'receipt']);
        Route::apiResource('transactions', \App\Http\Controllers\Api\V1\TransactionController::class);

        // Draft Transaction Routes
        Route::post('draft-transactions/{id}/confirm', [\App\Http\Controllers\Api\V1\DraftTransactionController::class, 'confirm']);
        Route::get('draft-transactions/{id}/receipt', [\App\Http\Controllers\Api\V1\DraftTransactionController::class, 'receipt']);
        Route::apiResource('draft-transactions', \App\Http\Controllers\Api\V1\DraftTransactionController::class);

        // Tag Routes
        Route::apiResource('tags', \App\Http\Controllers\Api\V1\TagController::class);

        // Budget Routes
        Route::get('budgets', [\App\Http\Controllers\Api\V1\BudgetController::class, 'index']);
        Route::post('budgets', [\App\Http\Controllers\Api\V1\BudgetController::class, 'store']);
        Route::delete('budgets/{id}', [\App\Http\Controllers\Api\V1\BudgetController::class, 'destroy']);

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

        // Notification Settings Routes
        Route::get('notification-settings', [\App\Http\Controllers\Api\V1\NotificationSettingsController::class, 'show']);
        Route::post('notification-settings', [\App\Http\Controllers\Api\V1\NotificationSettingsController::class, 'update']);

        // Notification History Log Routes
        Route::get('notifications', [\App\Http\Controllers\Api\V1\NotificationsHistoryController::class, 'index']);
        Route::post('notifications/{id}/read', [\App\Http\Controllers\Api\V1\NotificationsHistoryController::class, 'markAsRead']);
        Route::post('notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationsHistoryController::class, 'markAllAsRead']);

        // Personal Subscription Routes
        Route::get('personal-subscriptions', [\App\Http\Controllers\Api\V1\PersonalSubscriptionController::class, 'index']);
        Route::post('personal-subscriptions', [\App\Http\Controllers\Api\V1\PersonalSubscriptionController::class, 'store']);
        Route::delete('personal-subscriptions/{id}', [\App\Http\Controllers\Api\V1\PersonalSubscriptionController::class, 'destroy']);

        // Savings Goal Routes
        Route::get('savings-goals', [\App\Http\Controllers\Api\V1\SavingsGoalController::class, 'index']);
        Route::post('savings-goals', [\App\Http\Controllers\Api\V1\SavingsGoalController::class, 'store']);
        Route::delete('savings-goals/{id}', [\App\Http\Controllers\Api\V1\SavingsGoalController::class, 'destroy']);

        // Recurring Bill Routes
        Route::get('recurring-bills', [\App\Http\Controllers\Api\V1\RecurringBillController::class, 'index']);
        Route::post('recurring-bills', [\App\Http\Controllers\Api\V1\RecurringBillController::class, 'store']);
        Route::delete('recurring-bills/{id}', [\App\Http\Controllers\Api\V1\RecurringBillController::class, 'destroy']);
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
