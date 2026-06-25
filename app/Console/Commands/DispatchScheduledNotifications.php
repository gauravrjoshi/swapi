<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Transaction;
use App\Models\DatabaseNotification;
use App\Models\PersonalSubscription;
use App\Models\SavingsGoal;
use App\Models\RecurringBill;
use App\Models\UserNotificationSetting;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:dispatch-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch scheduled notifications based on user preferences and timezones';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting scheduled notification dispatcher...');

        // Retrieve all users who have registered notification settings
        $users = User::all();

        foreach ($users as $user) {
            try {
                $settings = UserNotificationSetting::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'timezone' => 'Asia/Kolkata',
                        'reminder_time' => '20:00:00',
                    ]
                );

                $timezone = $settings->timezone ?? 'Asia/Kolkata';
                $userTime = Carbon::now($timezone);
                $userHour = $userTime->hour;
                $userMinute = $userTime->minute;

                // 1. Daily Transaction Reminder (Runs at custom reminder_time)
                if ($settings->enable_daily_reminder) {
                    $reminderParts = explode(':', $settings->reminder_time);
                    $targetHour = isset($reminderParts[0]) ? (int)$reminderParts[0] : 20;
                    $targetMinute = isset($reminderParts[1]) ? (int)$reminderParts[1] : 0;

                    if ($userHour === $targetHour && $userMinute === $targetMinute) {
                        $this->dispatchDailyReminder($user, $userTime);
                    }
                }

                // 2. Daily Subscription & Recurring Bill Alerts (Runs daily at 9:00 AM)
                if ($userHour === 9 && $userMinute === 0) {
                    if ($settings->enable_subscription_reminders) {
                        $this->dispatchSubscriptionReminders($user, $userTime);
                    }
                    if ($settings->enable_recurring_reminders) {
                        $this->dispatchRecurringBillReminders($user, $userTime);
                    }
                }

                // 3. Weekly Spending Summary (Runs Sunday at 8:00 PM)
                if ($settings->enable_weekly_summary && $userTime->dayOfWeek === Carbon::SUNDAY && $userHour === 20 && $userMinute === 0) {
                    $this->dispatchWeeklySummary($user, $userTime);
                }

                // 4. Monthly Financial Report & Savings Goal Updates (Runs 1st of the month at 9:00 AM)
                if ($userTime->day === 1 && $userHour === 9 && $userMinute === 0) {
                    if ($settings->enable_monthly_report) {
                        $this->dispatchMonthlyReport($user, $userTime);
                    }
                    if ($settings->enable_savings_goals) {
                        $this->dispatchSavingsGoalProgress($user, $userTime);
                    }
                }

                // 5. Streaks Tracking (Runs daily at 10:00 PM)
                if ($settings->enable_streaks && $userHour === 22 && $userMinute === 0) {
                    $this->dispatchStreakMilestones($user, $userTime);
                }

                // 6. Unusual Spending & Insights (Runs Friday at 6:00 PM)
                if ($userTime->dayOfWeek === Carbon::FRIDAY && $userHour === 18 && $userMinute === 0) {
                    if ($settings->enable_unusual_spending) {
                        $this->dispatchUnusualSpendingAlerts($user, $userTime);
                    }
                    if ($settings->enable_insights) {
                        $this->dispatchFinancialInsights($user, $userTime);
                    }
                }

            } catch (\Throwable $e) {
                Log::error("Error processing scheduled notifications for user {$user->id}: " . $e->getMessage());
                $this->error("Error processing scheduled notifications for user {$user->id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }

        $this->info('Scheduled notification dispatcher completed.');
    }

    /**
     * Dispatch daily transaction reminder if no transactions logged today.
     */
    protected function dispatchDailyReminder(User $user, Carbon $userTime): void
    {
        $todayStr = $userTime->toDateString();

        // 1. Check if user already logged transactions today
        $hasTransactions = Transaction::where('user_id', $user->id)
            ->whereDate('date', $todayStr)
            ->exists();

        if ($hasTransactions) {
            return;
        }

        // 2. Prevent duplicate notifications on the same day
        $alreadySent = DatabaseNotification::where('user_id', $user->id)
            ->where('type', 'daily_reminder')
            ->whereBetween('created_at', [
                $userTime->copy()->startOfDay()->utc(),
                $userTime->copy()->endOfDay()->utc()
            ])
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->notificationService->sendToUser(
            $user,
            'Daily Transaction Reminder',
            "Don't forget to track your expenses today! Logging daily helps you stay on top of your budgets.",
            ['type' => 'daily_reminder']
        );
    }

    /**
     * Dispatch subscription renewal alerts.
     */
    protected function dispatchSubscriptionReminders(User $user, Carbon $userTime): void
    {
        $tomorrow = $userTime->copy()->addDay()->toDateString();
        $inSevenDays = $userTime->copy()->addDays(7)->toDateString();



        $subscriptions = PersonalSubscription::whereIn('user_id', function ($query) use ($user) {
                $query->select('id')
                    ->from('users')
                    ->where('unid', $user->unid);
            })
            ->where('is_active', true)
            ->whereIn('next_renewal_date', [$tomorrow, $inSevenDays])
            ->get();

        foreach ($subscriptions as $sub) {
            $daysLeft = (int) abs(Carbon::parse($sub->next_renewal_date)->diffInDays($userTime->copy()->startOfDay()));
            $amountStr = "₹" . number_format($sub->amount, 2);

            $title = "Subscription Renewal Alert";
            if ($daysLeft == 1) {
                $body = "Your {$sub->name} subscription renews tomorrow ({$amountStr}).";
            } else {
                $body = "Your {$sub->name} subscription renews in 7 days ({$amountStr}).";
            }

            $this->notificationService->sendToUser($user, $title, $body, [
                'type' => 'subscription',
                'subscription_id' => (string) $sub->id,
            ]);
        }
    }

    /**
     * Dispatch recurring bills due alerts.
     */
    protected function dispatchRecurringBillReminders(User $user, Carbon $userTime): void
    {
        $tomorrow = $userTime->copy()->addDay()->toDateString();
        $inThreeDays = $userTime->copy()->addDays(3)->toDateString();

        $bills = RecurringBill::whereIn('user_id', function ($query) use ($user) {
                $query->select('id')
                    ->from('users')
                    ->where('unid', $user->unid);
            })
            ->where('is_active', true)
            ->whereIn('next_due_date', [$tomorrow, $inThreeDays])
            ->get();

        foreach ($bills as $bill) {
            $daysLeft = (int) abs(Carbon::parse($bill->next_due_date)->diffInDays($userTime->copy()->startOfDay()));
            $amountStr = "₹" . number_format($bill->amount, 2);

            $title = $bill->type === 'income' ? "Recurring Credit Reminder" : "Utility Bill Reminder";
            if ($daysLeft == 1) {
                $body = $bill->type === 'income' 
                    ? "Your {$bill->name} is expected tomorrow ({$amountStr})." 
                    : "Your {$bill->name} bill is due tomorrow ({$amountStr}).";
            } else {
                $body = $bill->type === 'income' 
                    ? "Your {$bill->name} is expected in 3 days ({$amountStr})." 
                    : "Your {$bill->name} bill is due in 3 days ({$amountStr}).";
            }

            $this->notificationService->sendToUser($user, $title, $body, [
                'type' => 'recurring_transaction',
                'recurring_bill_id' => (string) $bill->id,
            ]);
        }
    }

    /**
     * Dispatch weekly spent summaries.
     */
    protected function dispatchWeeklySummary(User $user, Carbon $userTime): void
    {
        $startOfWeek = $userTime->copy()->startOfWeek()->toDateString();
        $endOfWeek = $userTime->copy()->endOfWeek()->toDateString();

        $startOfLastWeek = $userTime->copy()->subWeek()->startOfWeek()->toDateString();
        $endOfLastWeek = $userTime->copy()->subWeek()->endOfWeek()->toDateString();

        // Calculate this week's spent
        $thisWeekSpent = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->sum('amount');

        // Calculate last week's spent
        $lastWeekSpent = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfLastWeek, $endOfLastWeek])
            ->sum('amount');

        // Top category this week
        $topCategoryRow = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->select('tag')
            ->groupBy('tag')
            ->selectRaw('tag, SUM(amount) as total')
            ->orderBy('total', 'desc')
            ->first();

        $topCategory = $topCategoryRow ? $topCategoryRow->tag : 'N/A';

        $body = "You spent ₹" . number_format($thisWeekSpent, 2) . " this week.";
        if ($topCategory !== 'N/A') {
            $body .= " Top category: {$topCategory}.";
        }

        if ($lastWeekSpent > 0) {
            $diffPercent = (($lastWeekSpent - $thisWeekSpent) / $lastWeekSpent) * 100;
            if ($diffPercent > 0) {
                $body .= " Your spending decreased by " . number_format($diffPercent, 0) . "% compared to last week.";
            } else {
                $body .= " Your spending increased by " . number_format(abs($diffPercent), 0) . "% compared to last week.";
            }
        }

        $this->notificationService->sendToUser(
            $user,
            'Weekly Spending Summary',
            $body,
            ['type' => 'weekly_summary']
        );
    }

    /**
     * Dispatch monthly report of savings.
     */
    protected function dispatchMonthlyReport(User $user, Carbon $userTime): void
    {
        $lastMonth = $userTime->copy()->subMonth();
        $startOfLastMonth = $lastMonth->copy()->startOfMonth()->toDateString();
        $endOfLastMonth = $lastMonth->copy()->endOfMonth()->toDateString();

        $credits = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $debits = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        $saved = $credits - $debits;
        $monthName = $lastMonth->format('F');

        $this->notificationService->sendToUser(
            $user,
            'Monthly Financial Report',
            "Your {$monthName} financial report is ready! You saved ₹" . number_format(max(0, $saved), 2) . " this month.",
            ['type' => 'monthly_report']
        );
    }

    /**
     * Dispatch savings goals progress.
     */
    protected function dispatchSavingsGoalProgress(User $user, Carbon $userTime): void
    {
        $goals = SavingsGoal::where('user_id', $user->id)->get();

        foreach ($goals as $goal) {
            $target = (float) $goal->target_amount;
            $current = (float) $goal->current_amount;
            if ($target <= 0) continue;

            $percent = min(100, round(($current / $target) * 100));
            $remaining = max(0, $target - $current);

            $title = "Savings Goal Progress";
            
            // Alternates format based on progress
            if ($percent >= 100) {
                $body = "Congratulations! You reached 100% of your {$goal->name} savings goal.";
            } elseif ($percent >= 75) {
                $body = "You are {$percent}% towards your {$goal->name} goal. Almost there!";
            } else {
                $body = "Only ₹" . number_format($remaining, 2) . " more needed to reach your {$goal->name} target.";
            }

            $this->notificationService->sendToUser($user, $title, $body, [
                'type' => 'savings_goal',
                'savings_goal_id' => (string) $goal->id,
            ]);
        }
    }

    /**
     * Dispatch streak tracking alerts.
     */
    protected function dispatchStreakMilestones(User $user, Carbon $userTime): void
    {
        // Calculate daily logging streak (consecutive days of transactions)
        $streak = 0;
        $checkDate = $userTime->copy();

        while (true) {
            $hasTx = Transaction::where('user_id', $user->id)
                ->whereDate('date', $checkDate->toDateString())
                ->exists();

            if ($hasTx) {
                $streak++;
                $checkDate->subDay();
            } else {
                break;
            }
        }

        if ($streak === 7) {
            $this->notificationService->sendToUser(
                $user,
                '🏆 Streak Achievement!',
                "7 days streak of expense tracking! You are building excellent financial habits.",
                ['type' => 'streak', 'streak_days' => '7']
            );
        }
    }

    /**
     * Dispatch alerts if spending on a category is 40% higher than average.
     */
    protected function dispatchUnusualSpendingAlerts(User $user, Carbon $userTime): void
    {
        $startOfMonth = $userTime->copy()->startOfMonth()->toDateString();
        $endOfMonth = $userTime->copy()->endOfMonth()->toDateString();

        // 1. Check for Category Spending 40% higher than usual (past 3 months average)
        $thisMonthSpentByCategory = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->groupBy('tag')
            ->selectRaw('tag, SUM(amount) as total')
            ->get();

        foreach ($thisMonthSpentByCategory as $spentRow) {
            $tag = $spentRow->tag;
            $currentTotal = (float) $spentRow->total;

            $threeMonthsAgo = $userTime->copy()->subMonths(3)->startOfMonth()->toDateString();
            $lastMonthEnd = $userTime->copy()->subMonth()->endOfMonth()->toDateString();

            $avgSpent = (float) Transaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->where('tag', $tag)
                ->whereBetween('date', [$threeMonthsAgo, $lastMonthEnd])
                ->sum('amount') / 3;

            if ($avgSpent > 100 && $currentTotal >= ($avgSpent * 1.4)) {
                $percent = round((($currentTotal - $avgSpent) / $avgSpent) * 100);
                $this->notificationService->sendToUser(
                    $user,
                    '⚠️ Unusual Spending Alert',
                    "Your {$tag} expenses are {$percent}% higher than usual this month.",
                    ['type' => 'unusual_spending', 'tag' => $tag]
                );
            }
        }

        // 2. Check for Large Transactions this week (> ₹15,000)
        $startOfWeek = $userTime->copy()->startOfWeek()->toDateString();
        $endOfWeek = $userTime->copy()->endOfWeek()->toDateString();

        $largeTx = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->where('amount', '>=', 15000)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->first();

        if ($largeTx) {
            $this->notificationService->sendToUser(
                $user,
                '🔍 Large Transaction Detected',
                "Large transaction detected on your account: ₹" . number_format($largeTx->amount, 2) . " for '{$largeTx->tag}'.",
                ['type' => 'unusual_spending', 'transaction_id' => (string) $largeTx->id]
            );
        }
    }

    /**
     * Dispatch smart insights and spending suggestions.
     */
    protected function dispatchFinancialInsights(User $user, Carbon $userTime): void
    {
        $startOfMonth = $userTime->copy()->startOfMonth()->toDateString();
        $endOfMonth = $userTime->copy()->endOfMonth()->toDateString();

        $totalSpent = (float) Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        if ($totalSpent <= 0) return;

        // Group by tags
        $categoryTotals = Transaction::where('user_id', $user->id)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->groupBy('tag')
            ->selectRaw('tag, SUM(amount) as total')
            ->orderBy('total', 'desc')
            ->first();

        if ($categoryTotals) {
            $tag = $categoryTotals->tag;
            $tagTotal = (float) $categoryTotals->total;
            $percent = round(($tagTotal / $totalSpent) * 100);

            if ($percent >= 30) {
                $this->notificationService->sendToUser(
                    $user,
                    '📊 Financial Insights',
                    "{$tag} accounts for {$percent}% of your monthly expenses. Consider setting a budget limit to optimize savings.",
                    ['type' => 'insight', 'tag' => $tag]
                );
            }
        }
    }
}
