<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('timezone')->default('UTC');
            $table->time('reminder_time')->default('20:00:00');
            $table->boolean('enable_daily_reminder')->default(true);
            $table->boolean('enable_budget_alerts')->default(true);
            $table->boolean('enable_weekly_summary')->default(true);
            $table->boolean('enable_monthly_report')->default(true);
            $table->boolean('enable_savings_goals')->default(true);
            $table->boolean('enable_subscription_reminders')->default(true);
            $table->boolean('enable_low_balance_alerts')->default(true);
            $table->boolean('enable_recurring_reminders')->default(true);
            $table->boolean('enable_streaks')->default(true);
            $table->boolean('enable_unusual_spending')->default(true);
            $table->boolean('enable_insights')->default(true);
            $table->decimal('low_balance_threshold', 12, 2)->default(1000.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_settings');
    }
};
