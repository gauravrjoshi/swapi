<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    /**
     * Display the user's notification settings.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $settings = UserNotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'timezone' => 'Asia/Kolkata', // Default to India Standard Time since user's local time is GMT+5:30
                'reminder_time' => '20:00:00',
                'enable_daily_reminder' => true,
                'enable_budget_alerts' => true,
                'enable_weekly_summary' => true,
                'enable_monthly_report' => true,
                'enable_savings_goals' => true,
                'enable_subscription_reminders' => true,
                'enable_low_balance_alerts' => true,
                'enable_recurring_reminders' => true,
                'enable_streaks' => true,
                'enable_unusual_spending' => true,
                'enable_insights' => true,
                'low_balance_threshold' => 1000.00,
            ]
        );

        return response()->json($settings);
    }

    /**
     * Update the user's notification settings.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $settings = UserNotificationSetting::firstOrCreate(['user_id' => $user->id]);

        $validated = $request->validate([
            'timezone' => 'sometimes|string',
            'reminder_time' => 'sometimes|string', // Validated as string representation (H:i or H:i:s)
            'enable_daily_reminder' => 'sometimes|boolean',
            'enable_budget_alerts' => 'sometimes|boolean',
            'enable_weekly_summary' => 'sometimes|boolean',
            'enable_monthly_report' => 'sometimes|boolean',
            'enable_savings_goals' => 'sometimes|boolean',
            'enable_subscription_reminders' => 'sometimes|boolean',
            'enable_low_balance_alerts' => 'sometimes|boolean',
            'enable_recurring_reminders' => 'sometimes|boolean',
            'enable_streaks' => 'sometimes|boolean',
            'enable_unusual_spending' => 'sometimes|boolean',
            'enable_insights' => 'sometimes|boolean',
            'low_balance_threshold' => 'sometimes|numeric|min:0',
        ]);

        $settings->update($validated);

        return response()->json($settings);
    }
}
