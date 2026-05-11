<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Get all dashboard metrics and chart data globally across all users.
     */
    public function getDashboardData(?int $userId = null, array $params = []): array
    {
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        
        $accounts = $this->accountService->getAccounts(null);

        // Core Metrics (Global)
        $credits = Transaction::where('type', 'credit')
            ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
            ->sum('amount');

        $debits = Transaction::where('type', 'debit')
            ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
            ->sum('amount');

        $totalGeneral = $accounts->where('account_type', 'general')->sum('balance');
        $totalSavings = $accounts->where('account_type', 'savings')->sum('balance');
        $totalAssets = $totalGeneral + $totalSavings;
        $totalLiabilities = $accounts->where('account_type', 'liability')->sum('balance');
        $netWorth = $totalAssets - $totalLiabilities;

        $periodSavings = Transaction::where('tag', 'savings')
            ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
            ->sum('amount');

        $growthData = $this->getMonthlySavingsGrowth();

        $expenseFilterStart = $params['expense_start_date'] ?? $startDate;
        $expenseFilterEnd = $params['expense_end_date'] ?? $endDate;
        $expenseData = $this->getExpenseDistribution($expenseFilterStart, $expenseFilterEnd);

        // User Comparison Data
        $userFilterStart = $params['user_start_date'] ?? $startDate;
        $userFilterEnd = $params['user_end_date'] ?? $endDate;
        $userComparison = $this->getUserComparison($userFilterStart, $userFilterEnd);

        return [
            'metrics' => [
                'totalCredits' => (float) $credits,
                'totalDebits' => (float) $debits,
                'netProfit' => (float) ($credits - $debits),
                'totalGeneral' => (float) $totalGeneral,
                'totalSavings' => (float) $totalSavings,
                'totalAssets' => (float) $totalAssets,
                'totalLiabilities' => (float) $totalLiabilities,
                'netWorth' => (float) $netWorth,
                'periodSavings' => (float) $periodSavings,
            ],
            'growthChart' => $growthData,
            'expenseChart' => $expenseData,
            'userChart' => $userComparison,
            'accounts' => $accounts
        ];
    }

    /**
     * Get monthly comparison data globally for the last 6 months.
     * Savings is specifically calculated from transactions with the 'savings' tag.
     */
    public function getMonthlySavingsGrowth(int $months = 6): array
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end = $date->copy()->endOfMonth()->toDateString();

            $income = Transaction::where('type', 'credit')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $expense = Transaction::where('type', 'debit')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            // Savings is sum of transactions tagged with 'savings'
            $savings = Transaction::where('tag', 'savings')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $data[] = [
                'label' => $date->format('M Y'),
                'income' => (float) $income,
                'expense' => (float) $expense,
                'savings' => (float) $savings,
            ];
        }
        return $data;
    }

    /**
     * Get expense distribution globally by tag for a specific date range.
     */
    public function getExpenseDistribution(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Transaction::where('type', 'debit')
            ->leftJoin('tags', 'transactions.tag', '=', 'tags.name')
            ->select('transactions.tag', DB::raw('SUM(transactions.amount) as total'), 'tags.color');

        if ($startDate) {
            $query->where('transactions.date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('transactions.date', '<=', $endDate);
        }

        return $query->groupBy('transactions.tag', 'tags.color')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->tag ?? 'Uncategorized',
                    'amount' => (float) $item->total,
                    'color' => $item->color ?? '#94a3b8'
                ];
            })
            ->toArray();
    }

    /**
     * Compare savings across users for a given date range.
     * Savings is specifically calculated from transactions with the 'savings' tag.
     */
    public function getUserComparison(?string $startDate = null, ?string $endDate = null): array
    {
        $users = User::all();
        $comparison = [];

        foreach ($users as $user) {
            // Savings is sum of transactions tagged with 'savings'
            $savings = Transaction::where('user_id', $user->id)
                ->where('tag', 'savings')
                ->when($startDate, fn($q) => $q->where('date', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('date', '<=', $endDate))
                ->sum('amount');

            if ($savings > 0) {
                $comparison[] = [
                    'name' => $user->name,
                    'savings' => (float) $savings,
                ];
            }
        }

        // Sort by savings descending
        usort($comparison, fn($a, $b) => $b['savings'] <=> $a['savings']);

        return $comparison;
    }
}
