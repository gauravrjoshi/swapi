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
        Route::post('tasks/{task}/reminders', [\App\Http\Controllers\Api\V1\TaskReminderController::class, 'store']);
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
