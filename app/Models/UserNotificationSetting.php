<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'timezone',
        'reminder_time',
        'enable_daily_reminder',
        'enable_budget_alerts',
        'enable_weekly_summary',
        'enable_monthly_report',
        'enable_savings_goals',
        'enable_subscription_reminders',
        'enable_low_balance_alerts',
        'enable_recurring_reminders',
        'enable_streaks',
        'enable_unusual_spending',
        'enable_insights',
        'low_balance_threshold',
    ];

    protected $casts = [
        'enable_daily_reminder' => 'boolean',
        'enable_budget_alerts' => 'boolean',
        'enable_weekly_summary' => 'boolean',
        'enable_monthly_report' => 'boolean',
        'enable_savings_goals' => 'boolean',
        'enable_subscription_reminders' => 'boolean',
        'enable_low_balance_alerts' => 'boolean',
        'enable_recurring_reminders' => 'boolean',
        'enable_streaks' => 'boolean',
        'enable_unusual_spending' => 'boolean',
        'enable_insights' => 'boolean',
        'low_balance_threshold' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
