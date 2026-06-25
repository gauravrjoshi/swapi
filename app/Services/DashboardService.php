<?php

namespace App\Services;

use App\Models\Transaction;
use App\Enums\PermissionEmnum;
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
        $authUser = auth()->user();
        $workspaceWide = false;

        if ($authUser) {
            $hasPermission = $authUser->is_admin ||
                $authUser->hasRole('admin', 'web') ||
                $authUser->hasPermissionTo(PermissionEmnum::VIEW_ALL_DASHBOARDS->value, 'web');
            if ($hasPermission) {
                if ($userId === null) {
                    $workspaceWide = true;
                }
            } else {
                $userId = $authUser->id;
            }
        } else {
            if ($userId === null) {
                $workspaceWide = true;
            }
        }

        $accounts = $this->accountService->getAccounts($userId, $workspaceWide);

        $summary = $this->getFinancialSummary($userId, $startDate, $endDate, $accounts);
        $monthlySavings = $this->getSavingsMetrics($userId, $startDate, $endDate);
        $chartData = $this->getMonthlyChartData($userId);
        $expenseChart = $this->getExpenseChartData($userId, $startDate, $endDate);
        $budgets = $this->getBudgetsSummary($userId);

        return array_merge($summary, [
            'accounts' => $accounts,
            'monthlySavings' => $monthlySavings,
            'chartData' => $chartData,
            'expenseChart' => $expenseChart,
            'budgets' => $budgets,
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
        $debitsPeriod = $this->getDebitTransactions($userId, $startDate, $endDate);

        $targetUserId = $userId ?: auth()->id();
        /** @var Collection $tags */
        $tags = $targetUserId ? $this->tagService->getTags($targetUserId) : collect();

        return $this->buildExpenseChartMap($debitsPeriod, $tags);
    }

    /**
     * Get debit transactions with optionally filtered date and user.
     *
     * @param int|null $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    protected function getDebitTransactions(?int $userId, ?string $startDate, ?string $endDate): Collection
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

        return $debitsPeriodQuery->get();
    }

    /**
     * Construct expense chart data from debit transactions and tags.
     *
     * @param Collection $debitsPeriod
     * @param Collection $tags
     * @return array
     */
    protected function buildExpenseChartMap(Collection $debitsPeriod, Collection $tags): array
    {
        /** @var array<string, string> $tagColorMap */
        $tagColorMap = $tags->pluck('color', 'name')->toArray();
        /** @var array<string, int|null> $tagIdMap */
        $tagIdMap = $tags->pluck('id', 'name')->toArray();
        /** @var Collection $groupedDebits */
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

    protected function getBudgetsSummary(?int $userId): array
    {
        if (!$userId) {
            return [];
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            return [];
        }

        $budgets = \App\Models\Budget::whereIn('user_id', function ($query) use ($user) {
            $query->select('id')
                ->from('users')
                ->where('unid', $user->unid);
        })->get();

        $tags = $this->tagService->getTags($userId);
        $tagColorMap = $tags->pluck('color', 'name')->all();

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        foreach ($budgets as $b) {
            $spent = Transaction::query()
                ->whereIn('user_id', function ($query) use ($user) {
                    $query->select('id')
                        ->from('users')
                        ->where('unid', $user->unid);
                })
                ->where('type', 'debit')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where(function ($query) use ($b) {
                    $query->where('tag', $b->tag);
                    if ($b->tag_id !== null) {
                        $query->orWhere('tag_id', $b->tag_id);
                    }
                })
                ->sum('amount');

            $b->spent = (float) $spent;
            $b->color = $tagColorMap[$b->tag] ?? '#6366f1';
        }

        return $budgets->toArray();
    }
}
