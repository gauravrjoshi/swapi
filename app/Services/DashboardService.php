<?php

namespace App\Services;

use App\Models\Transaction;

class DashboardService
{
    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Get all dashboard data for a specific user.
     */
    public function getDashboardData(int $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = $this->accountService->getAccounts($userId);

        $creditsQuery = Transaction::where('type', 'credit');
        $debitsQuery = Transaction::where('type', 'debit');

        if ($startDate) {
            $creditsQuery->where('date', '>=', $startDate);
            $debitsQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $creditsQuery->where('date', '<=', $endDate);
            $debitsQuery->where('date', '<=', $endDate);
        }

        $credits = $creditsQuery->sum('amount');
        $debits = $debitsQuery->sum('amount');
        $net = $credits - $debits;

        // Total assets = general + savings
        $totalGeneral = $accounts->where('account_type', 'general')->sum('balance');
        $totalSavings = $accounts->where('account_type', 'savings')->sum('balance');
        $totalAssets = $totalGeneral + $totalSavings;

        $totalLiabilities = $accounts->where('account_type', 'liability')->sum('balance');
        $netWorth = $totalAssets - $totalLiabilities;

        // Period savings (transfers tagged as 'savings') — follows the same filter range
        $savingsQuery = Transaction::where('type', 'transfer')
            ->where('tag', 'like', 'savings');

        if ($startDate) {
            $savingsQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $savingsQuery->where('date', '<=', $endDate);
        }

        $monthlySavings = $savingsQuery->sum('amount');

        // Monthly savings chart data (last 6 months)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $mSavings = Transaction::where('type', 'transfer')
                ->whereHas('toAccount', function ($q) {
                    $q->where('account_type', 'savings')
                      ->orWhere('is_savings', true);
                })
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $chartData[] = [
                'label' => $month->format('M Y'),
                'savings' => (float) $mSavings,
            ];
        }

        return [
            'accounts' => $accounts,
            'totalCredits' => $credits,
            'totalDebits' => $debits,
            'netProfit' => $net,
            'totalGeneral' => $totalGeneral,
            'totalSavings' => $totalSavings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'netWorth' => $netWorth,
            'monthlySavings' => (float) $monthlySavings,
            'chartData' => $chartData,
        ];
    }
}
