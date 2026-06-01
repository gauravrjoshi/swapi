<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Collection;

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
     * Get all dashboard data for a specific user or workspace wide.
     *
     * @param int|null $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getDashboardData(?int $userId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = $this->accountService->getAccounts($userId);

        $summary = $this->getFinancialSummary($userId, $startDate, $endDate, $accounts);
        $monthlySavings = $this->getSavingsMetrics($userId, $startDate, $endDate);
        $chartData = $this->getMonthlyChartData($userId);
        $expenseChart = $this->getExpenseChartData($userId, $startDate, $endDate);

        return array_merge($summary, [
            'accounts' => $accounts,
            'monthlySavings' => $monthlySavings,
            'chartData' => $chartData,
            'expenseChart' => $expenseChart,
        ]);
    }

    /**
     * Calculate financial summaries and aggregated balances.
     *
     * @param int|null $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param Collection $accounts
     * @return array
     */
    protected function getFinancialSummary(?int $userId, ?string $startDate, ?string $endDate, Collection $accounts): array
    {
        $creditsQuery = Transaction::where('type', 'credit');
        $debitsQuery = Transaction::where('type', 'debit');

        if ($userId) {
            $creditsQuery->where('user_id', $userId);
            $debitsQuery->where('user_id', $userId);
        }

        if ($startDate) {
            $creditsQuery->where('date', '>=', $startDate);
            $debitsQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $creditsQuery->where('date', '<=', $endDate);
            $debitsQuery->where('date', '<=', $endDate);
        }

        $credits = (float) $creditsQuery->sum('amount');
        $debits = (float) $debitsQuery->sum('amount');
        $net = $credits - $debits;

        // Total assets = general + savings
        $totalGeneral = (float) $accounts->where('account_type', 'general')->sum('balance');
        $totalSavings = (float) $accounts->where('account_type', 'savings')->sum('balance');
        $totalAssets = $totalGeneral + $totalSavings;

        $totalLiabilities = (float) $accounts->where('account_type', 'liability')->sum('balance');
        $netWorth = $totalAssets - $totalLiabilities;

        return [
            'totalCredits' => $credits,
            'totalDebits' => $debits,
            'netProfit' => $net,
            'totalGeneral' => $totalGeneral,
            'totalSavings' => $totalSavings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'netWorth' => $netWorth,
        ];
    }

    /**
     * Get period savings transfers.
     *
     * @param int|null $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return float
     */
    protected function getSavingsMetrics(?int $userId, ?string $startDate, ?string $endDate): float
    {
        $savingsQuery = Transaction::where('type', 'transfer')
            ->where('tag', 'like', 'savings');

        if ($userId) {
            $savingsQuery->where('user_id', $userId);
        }

        if ($startDate) {
            $savingsQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $savingsQuery->where('date', '<=', $endDate);
        }

        return (float) $savingsQuery->sum('amount');
    }

    /**
     * Get monthly chart data for the last 6 months.
     *
     * @param int|null $userId
     * @return array
     */
    protected function getMonthlyChartData(?int $userId): array
    {
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $mSavingsQuery = Transaction::where('type', 'transfer')
                ->whereHas('toAccount', function ($q) {
                    $q->where('account_type', 'savings')
                        ->orWhere('is_savings', true);
                })
                ->whereBetween('date', [$monthStart, $monthEnd]);

            $mIncomeQuery = Transaction::where('type', 'credit')
                ->where(function ($q) {
                    $q->whereNull('from_account_id')
                        ->orWhereNull('to_account_id');
                })
                ->whereBetween('date', [$monthStart, $monthEnd]);

            $mExpenseQuery = Transaction::where('type', 'debit')
                ->where(function ($q) {
                    $q->whereNull('from_account_id')
                        ->orWhereNull('to_account_id');
                })
                ->whereBetween('date', [$monthStart, $monthEnd]);

            if ($userId) {
                $mSavingsQuery->where('user_id', $userId);
                $mIncomeQuery->where('user_id', $userId);
                $mExpenseQuery->where('user_id', $userId);
            }

            $chartData[] = [
                'label' => $month->format('M Y'),
                'income' => (float) $mIncomeQuery->sum('amount'),
                'expense' => (float) $mExpenseQuery->sum('amount'),
                'savings' => (float) $mSavingsQuery->sum('amount'),
            ];
        }

        return $chartData;
    }

    /**
     * Get aggregated debits/expenses grouped by tags.
     *
     * @param int|null $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    protected function getExpenseChartData(?int $userId, ?string $startDate, ?string $endDate): array
    {
        $debitsPeriodQuery = Transaction::where('type', 'debit')
            ->where(function ($q) {
                $q->whereNull('from_account_id')
                    ->orWhereNull('to_account_id');
            });

        if ($userId) {
            $debitsPeriodQuery->where('user_id', $userId);
        }

        if ($startDate) {
            $debitsPeriodQuery->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $debitsPeriodQuery->where('date', '<=', $endDate);
        }
        $debitsPeriod = $debitsPeriodQuery->get();

        $targetUserId = $userId ?: auth()->id();
        $tags = $targetUserId ? $this->tagService->getTags($targetUserId) : collect();
        $tagColorMap = $tags->pluck('color', 'name')->toArray();
        $tagIdMap = $tags->pluck('id', 'name')->toArray();
        $groupedDebits = $debitsPeriod->groupBy('tag');

        $expenseChartMap = [];
        foreach ($tags as $tag) {
            $name = $tag->name;
            $color = $tag->color;
            $tagId = $tag->id;
            $amount = 0.0;
            if ($groupedDebits->has($name)) {
                $amount = (float) $groupedDebits->get($name)->sum('amount');
            }
            $expenseChartMap[$name] = [
                'label' => $name,
                'amount' => $amount,
                'color' => $color,
                'tag_id' => $tagId,
            ];
        }

        // Also handle "Uncategorized" if any transactions have empty/null/unmapped tags
        foreach ($groupedDebits as $tagName => $transactions) {
            $name = $tagName ?: 'Uncategorized';
            if (!isset($expenseChartMap[$name])) {
                $color = $tagColorMap[$name] ?? '#94a3b8';
                $tagId = $tagIdMap[$name] ?? null;
                $expenseChartMap[$name] = [
                    'label' => $name,
                    'amount' => (float) $transactions->sum('amount'),
                    'color' => $color,
                    'tag_id' => $tagId,
                ];
            }
        }

        return collect($expenseChartMap)
            ->filter(fn($item) => (float) $item['amount'] != 0.0)
            ->sortBy(fn($item) => [-$item['amount'], $item['label']])
            ->values()
            ->toArray();
    }
}
