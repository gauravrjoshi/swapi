<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }
    /**
     * Get all dashboard data for a specific user.
     *
     * @param int $userId
     * @return array
     */
    public function getDashboardData(int $userId): array
    {
        $accounts = $this->accountService->getAccounts($userId);

        $credits = Transaction::/* where('user_id', $userId)-> */ where('type', 'credit')->sum('amount');
        $debits = Transaction::/* where('user_id', $userId)-> */ where('type', 'debit')->sum('amount');
        $net = $credits - $debits;

        // Total savings = sum of balances of accounts marked as savings
        $totalSavings = $accounts->where('is_savings', true)->sum('balance');

        // This month's net change (credits - debits)
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $thisMonthCredits = Transaction::/* where('user_id', $userId)
-> */ whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('type', 'credit')->sum('amount');
        $thisMonthDebits = Transaction::/* where('user_id', $userId)
-> */ whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('type', 'debit')->sum('amount');
        $monthlyChange = $thisMonthCredits - $thisMonthDebits;

        // Monthly savings = transfers tagged as 'savings'
        $monthlySavings = Transaction::/* where('user_id', $userId)
-> */ where('type', 'transfer')
            ->where('tag', 'like', 'savings')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Monthly savings chart data (last 6 months)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $mCredits = Transaction::/* where('user_id', $userId)
-> */ whereBetween('date', [$monthStart, $monthEnd])
                ->where('type', 'credit')->sum('amount');
            $mDebits = Transaction::/* where('user_id', $userId)
-> */ whereBetween('date', [$monthStart, $monthEnd])
                ->where('type', 'debit')->sum('amount');

            $chartData[] = [
                'label' => $month->format('M Y'),
                'savings' => (float) ($mCredits - $mDebits),
            ];
        }

        return [
            'accounts' => $accounts,
            'totalCredits' => $credits,
            'totalDebits' => $debits,
            'netProfit' => $net,
            'totalSavings' => $totalSavings,
            'monthlyChange' => (float) $monthlyChange,
            'monthlySavings' => (float) $monthlySavings,
            'chartData' => $chartData,
        ];
    }
}
