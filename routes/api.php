<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth Routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [UserController::class, 'me']);

        // Task Routes
        Route::apiResource('tasks', \App\Http\Controllers\Api\V1\TaskController::class);

        // Transaction Routes
        Route::post('transactions/import', [\App\Http\Controllers\Api\V1\TransactionController::class, 'import']);
        Route::apiResource('transactions', \App\Http\Controllers\Api\V1\TransactionController::class);

        // Task Reminder Routes (Nested)
        Route::get('tasks/{task}/reminders', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'index']);
        Route::post('tasks/{task}/reminders', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'store']);
        Route::put('tasks/{task}/reminders/{reminder}', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'update']);
        Route::delete('tasks/{task}/reminders/{reminder}', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'destroy']);
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
