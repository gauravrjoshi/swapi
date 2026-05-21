<?php

namespace App\Services;

use App\Models\Transaction;

class DashboardService
{
    protected $accountService;
    protected $tagService;

    public function __construct(AccountService $accountService, TagService $tagService)
    {
        $this->accountService = $accountService;
        $this->tagService = $tagService;
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

            $mIncome = Transaction::where('type', 'credit')
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $mExpense = Transaction::where('type', 'debit')
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->sum('amount');

            $chartData[] = [
                'label' => $month->format('M Y'),
                'income' => (float) $mIncome,
                'expense' => (float) $mExpense,
                'savings' => (float) $mSavings,
            ];
        }

        // Group period debits by category/tag to build the expense pie chart data
        $debitsPeriodQuery = Transaction::where('type', 'debit');
        if ($startDate) {
            $debitsPeriodQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $debitsPeriodQuery->where('date', '<=', $endDate);
        }
        $debitsPeriod = $debitsPeriodQuery->get();

        $tags = $this->tagService->getTags($userId);
        $tagColorMap = $tags->pluck('color', 'name')->toArray();
        $groupedDebits = $debitsPeriod->groupBy('tag');

        $expenseChartMap = [];
        foreach ($tags as $tag) {
            $name = $tag->name;
            $color = $tag->color;
            $amount = 0.0;
            if ($groupedDebits->has($name)) {
                $amount = (float) $groupedDebits->get($name)->sum('amount');
            }
            $expenseChartMap[$name] = [
                'label' => $name,
                'amount' => $amount,
                'color' => $color,
            ];
        }

        // Also handle "Uncategorized" if any transactions have empty/null/unmapped tags
        foreach ($groupedDebits as $tagName => $transactions) {
            $name = $tagName ?: 'Uncategorized';
            if (!isset($expenseChartMap[$name])) {
                $color = $tagColorMap[$name] ?? '#94a3b8';
                $expenseChartMap[$name] = [
                    'label' => $name,
                    'amount' => (float) $transactions->sum('amount'),
                    'color' => $color,
                ];
            }
        }

        $expenseChart = collect($expenseChartMap)
            ->sortBy(fn($item) => [-$item['amount'], $item['label']])
            ->values()
            ->toArray();

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
            'expenseChart' => $expenseChart,
        ];
    }
}
