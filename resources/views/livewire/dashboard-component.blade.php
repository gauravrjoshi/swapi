<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $confirmingTransactionDeletionId = null;
    
    // Global/Metrics Filter
    public $period = 'current_month';
    public $startDate = null;
    public $endDate = null;

    // Separate Expense Chart Filter
    public $expensePeriod = 'current_month';
    public $expenseStartDate = null;
    public $expenseEndDate = null;

    // Separate User Comparison Chart Filter
    public $userPeriod = 'current_month';
    public $userStartDate = null;
    public $userEndDate = null;

    public function mount()
    {
        $this->calculateGlobalDates();
        $this->calculateExpenseDates();
        $this->calculateUserDates();
    }

    public function updatedPeriod()
    {
        $this->calculateGlobalDates();
        $this->dispatch('charts-updated');
    }

    public function updatedExpensePeriod()
    {
        $this->calculateExpenseDates();
        $this->dispatch('charts-updated');
    }

    public function updatedUserPeriod()
    {
        $this->calculateUserDates();
        $this->dispatch('charts-updated');
    }

    protected function calculateGlobalDates()
    {
        [$this->startDate, $this->endDate] = $this->getDatesForPeriod($this->period);
    }

    protected function calculateExpenseDates()
    {
        [$this->expenseStartDate, $this->expenseEndDate] = $this->getDatesForPeriod($this->expensePeriod);
    }

    protected function calculateUserDates()
    {
        [$this->userStartDate, $this->userEndDate] = $this->getDatesForPeriod($this->userPeriod);
    }

    protected function getDatesForPeriod($period)
    {
        switch ($period) {
            case 'current_month':
                return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
            case 'last_month':
                return [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()];
            case 'last_3_months':
                return [now()->subMonths(2)->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
            case 'last_6_months':
                return [now()->subMonths(5)->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
            case 'last_quarter':
                $startOfQuarter = now()->subQuarter()->startOfQuarter();
                $endOfQuarter = now()->subQuarter()->endOfQuarter();
                return [$startOfQuarter->toDateString(), $endOfQuarter->toDateString()];
            case 'this_quarter':
                return [now()->startOfQuarter()->toDateString(), now()->endOfQuarter()->toDateString()];
            case 'this_year':
                return [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()];
            case 'all_time':
                return [null, null];
            default:
                return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        }
    }

    public function confirmTransactionDelete($id)
    {
        $this->confirmingTransactionDeletionId = $id;
    }

    public function cancelTransactionDelete()
    {
        $this->confirmingTransactionDeletionId = null;
    }

    public function deleteTransaction(App\Services\TransactionService $service)
    {
        $transaction = Transaction::findOrFail($this->confirmingTransactionDeletionId);
        $service->deleteTransaction($transaction);

        $this->confirmingTransactionDeletionId = null;
        session()->flash('message', 'Transaction deleted and balances updated.');
    }

    public function with(App\Services\DashboardService $service)
    {
        return $service->getDashboardData(Auth::id(), [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'expense_start_date' => $this->expenseStartDate,
            'expense_end_date' => $this->expenseEndDate,
            'user_start_date' => $this->userStartDate,
            'user_end_date' => $this->userEndDate,
        ]);
    }
};
?>

<div class="p-6 w-full mx-auto space-y-8 bg-slate-50 min-h-screen">
    <style>
        .chart-container { min-height: 400px; }
        @keyframes shimmer {
            0% { background-position: -468px 0; }
            100% { background-position: 468px 0; }
        }
        .skeleton {
            background: #f6f7f8;
            background-image: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-repeat: no-repeat;
            background-size: 800px 104px;
            display: inline-block;
            position: relative;
            animation-duration: 1.2s;
            animation-fill-mode: forwards;
            animation-iteration-count: infinite;
            animation-name: shimmer;
            animation-timing-function: linear;
        }
    </style>

    <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 gap-6">
        <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Financial Health</h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <p class="text-slate-500 font-medium text-sm">Overview for {{ Auth::user()->name }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Global Filter</label>
                <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-200">
                    <select wire:model.live="period" class="bg-transparent border-none text-sm font-black text-slate-700 focus:ring-0 cursor-pointer">
                        <option value="current_month">Current Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="all_time">All Time</option>
                    </select>
                </div>
            </div>

            <a href="/transactions/new"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-2xl font-black transition-all shadow-xl shadow-indigo-200 flex items-center gap-2 whitespace-nowrap mt-auto active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                New Transaction
            </a>
        </div>
    </header>

    <!-- Metrics Grid -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-2">
            <div class="flex flex-col">
                <h2 class="text-xl font-black text-slate-900">Core Performance</h2>
                <p class="text-xs font-bold text-slate-400 mt-0.5">
                    @if($startDate && $endDate)
                        {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M, Y') }}
                    @else
                        Full Account History
                    @endif
                </p>
            </div>
            <button @click="showBalances = !showBalances" class="h-10 w-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 transition-colors shadow-sm">
                <svg x-show="showBalances" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg x-show="!showBalances" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.076m3.313-3.313A9.973 9.973 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21m-2.101-2.101L3 3" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Credits -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 group transition-all hover:shadow-xl hover:-translate-y-1">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                        </svg>
                    </div>
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Income</span>
                </div>
                <p class="text-3xl font-black text-slate-900" x-text="showBalances ? '₹{{ number_format($metrics['totalCredits'], 2) }}' : '₹ ••••'"></p>
            </div>

            <!-- Debits -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 group transition-all hover:shadow-xl hover:-translate-y-1">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                        </svg>
                    </div>
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Expenses</span>
                </div>
                <p class="text-3xl font-black text-slate-900" x-text="showBalances ? '₹{{ number_format($metrics['totalDebits'], 2) }}' : '₹ ••••'"></p>
            </div>

            <!-- General Balance -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 group transition-all hover:shadow-xl hover:-translate-y-1">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <span class="text-xs font-black text-slate-400 uppercase tracking-widest">General Balance</span>
                </div>
                <p class="text-3xl font-black text-slate-900" x-text="showBalances ? '₹{{ number_format($metrics['totalGeneral'], 2) }}' : '₹ ••••'"></p>
            </div>

            <!-- Net Worth Card (Redesigned) -->
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border-l-4 border-l-amber-400 border border-slate-100 group transition-all hover:shadow-xl hover:-translate-y-1 relative overflow-hidden">
                <div class="space-y-6">
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Net Worth</span>
                        <p class="text-4xl font-black text-slate-900" x-text="showBalances ? '₹{{ number_format($metrics['netWorth'], 2) }}' : '₹ ••••'"></p>
                    </div>

                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-2">
                        <span>Assets: <span class="text-slate-600">₹{{ number_format($metrics['totalAssets'], 2) }}</span></span>
                        <span class="mx-1">•</span>
                        <span>Debt: <span class="text-slate-600">₹{{ number_format($metrics['totalLiabilities'], 2) }}</span></span>
                    </div>

                    <div class="flex items-center justify-between text-sm font-black">
                        <span class="text-emerald-600 uppercase tracking-tight">Savings A/C:</span>
                        <span class="text-slate-900" x-text="showBalances ? '₹{{ number_format($metrics['totalSavings'], 2) }}' : '₹ ••••'"></span>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-slate-50">
                        <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                        <p class="text-xs font-bold text-slate-500">
                            ₹{{ number_format($metrics['periodSavings'], 2) }} 
                            <span class="text-slate-400">saved 
                                @if($period === 'current_month') this month
                                @elseif($period === 'this_year') this year
                                @else for this period
                                @endif
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Savings Growth Chart -->
        <div class="space-y-4">
            <div class="flex items-center justify-between px-2">
                <div class="flex flex-col">
                    <h2 class="text-2xl font-black text-slate-900">Savings Growth</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Last 6 Months Trend</p>
                </div>
                <div class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-indigo-100">Trend Analysis</div>
            </div>
            <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 chart-container flex flex-col transition-all hover:shadow-md relative">
                <div id="growthChartData" data-chart='@json($growthChart)' class="hidden"></div>
                <div class="flex-1 relative">
                    <canvas id="growthChart" wire:ignore></canvas>
                </div>
            </div>
        </div>

        <!-- Expense Distribution Chart -->
        <div class="space-y-4">
            <div class="flex items-center justify-between px-2">
                <div class="flex flex-col">
                    <h2 class="text-2xl font-black text-slate-900">Expense Breakdown</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                        ({{ \Carbon\Carbon::parse($expenseStartDate)->format('M Y') }} 
                        @if(\Carbon\Carbon::parse($expenseStartDate)->format('M Y') !== \Carbon\Carbon::parse($expenseEndDate)->format('M Y'))
                            - {{ \Carbon\Carbon::parse($expenseEndDate)->format('M Y') }}
                        @endif)
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-sm">
                    <select wire:model.live="expensePeriod" class="bg-transparent border-none text-[10px] font-black text-slate-500 uppercase tracking-widest focus:ring-0 cursor-pointer p-1 pr-6">
                        <option value="current_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="last_3_months">Last 3 Months</option>
                        <option value="last_quarter">Last Quarter</option>
                        <option value="last_6_months">Last 6 Months</option>
                        <option value="this_year">This Year</option>
                    </select>
                </div>
            </div>
            <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 chart-container flex flex-col transition-all hover:shadow-md relative">
                <div id="expenseChartData" data-chart='@json($expenseChart)' data-credits="{{ (float) $metrics['totalCredits'] }}" class="hidden"></div>
                
                <div class="flex-1 relative flex items-center justify-center">
                    <div id="expenseChartEmptyState" class="{{ empty($expenseChart) ? 'block' : 'hidden' }} text-center space-y-4">
                        <div class="h-24 w-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto border-2 border-dashed border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                            </svg>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No Data Available</p>
                            <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">Try changing the date filter</p>
                        </div>
                    </div>
                    
                    <div class="w-full h-full {{ empty($expenseChart) ? 'hidden' : 'block' }}">
                        <canvas id="expenseChart" wire:ignore></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Comparison Row -->
    <div class="space-y-4">
        <div class="flex items-center justify-between px-2">
            <div class="flex flex-col">
                <h2 class="text-2xl font-black text-slate-900">User Savings Comparison</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                    ({{ \Carbon\Carbon::parse($userStartDate)->format('M Y') }} 
                    @if(\Carbon\Carbon::parse($userStartDate)->format('M Y') !== \Carbon\Carbon::parse($userEndDate)->format('M Y'))
                        - {{ \Carbon\Carbon::parse($userEndDate)->format('M Y') }}
                    @endif)
                </p>
            </div>
            <div class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-sm">
                <select wire:model.live="userPeriod" class="bg-transparent border-none text-[10px] font-black text-slate-500 uppercase tracking-widest focus:ring-0 cursor-pointer p-1 pr-6">
                    <option value="current_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="last_3_months">Last 3 Months</option>
                    <option value="last_quarter">Last Quarter</option>
                    <option value="last_6_months">Last 6 Months</option>
                    <option value="this_year">This Year</option>
                </select>
            </div>
        </div>
        <div class="bg-white p-8 rounded-[3rem] shadow-sm border border-slate-100 min-h-[400px] flex flex-col transition-all hover:shadow-md relative">
            <div id="userChartData" data-chart='@json($userChart)' class="hidden"></div>
            
            <div class="flex-1 relative">
                <div id="userChartEmptyState" class="{{ empty($userChart) ? 'flex' : 'hidden' }} absolute inset-0 items-center justify-center text-center space-y-4">
                    <div class="space-y-4">
                        <div class="h-24 w-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto border-2 border-dashed border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-black text-slate-400 uppercase tracking-widest">No user activity in this period</p>
                    </div>
                </div>
                
                <div class="w-full h-full {{ empty($userChart) ? 'hidden' : 'block' }}">
                    <canvas id="userChart" wire:ignore></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let growthChartInstance = null;
        let expenseChartInstance = null;
        let userChartInstance = null;

        document.addEventListener('livewire:navigated', initCharts);
        document.addEventListener('DOMContentLoaded', initCharts);
        document.addEventListener('charts-updated', () => setTimeout(initCharts, 100));

        function initCharts() {
            if (growthChartInstance) growthChartInstance.destroy();
            if (expenseChartInstance) expenseChartInstance.destroy();
            if (userChartInstance) userChartInstance.destroy();

            const growthDataEl = document.getElementById('growthChartData');
            const growthCtx = document.getElementById('growthChart');
            if (growthCtx && growthDataEl) {
                const data = JSON.parse(growthDataEl.dataset.chart);
                growthChartInstance = new Chart(growthCtx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.label),
                        datasets: [
                            { label: 'Income', data: data.map(d => d.income), backgroundColor: '#10b981', borderRadius: 8, barThickness: 15 },
                            { label: 'Expense', data: data.map(d => d.expense), backgroundColor: '#f43f5e', borderRadius: 8, barThickness: 15 },
                            { label: 'Savings', data: data.map(d => d.savings), backgroundColor: '#6366f1', borderRadius: 8, barThickness: 15 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, font: { weight: 'black', size: 10 }, padding: 20 } },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 16,
                                cornerRadius: 16,
                                callbacks: { label: (c) => ` ${c.dataset.label}: ₹${new Intl.NumberFormat('en-IN').format(c.parsed.y)}` }
                            }
                        },
                        scales: {
                            y: { grid: { borderDash: [5, 5], color: '#f1f5f9' }, ticks: { font: { weight: 'bold' }, color: '#94a3b8' } },
                            x: { grid: { display: false }, ticks: { font: { weight: 'black' }, color: '#64748b' } }
                        }
                    }
                });
            }

            const expenseDataEl = document.getElementById('expenseChartData');
            const expenseCtx = document.getElementById('expenseChart');
            if (expenseCtx && expenseDataEl) {
                const data = JSON.parse(expenseDataEl.dataset.chart);
                const totalIncome = parseFloat(expenseDataEl.dataset.credits || 0);
                const totalExpenses = data.reduce((a, b) => a + b.amount, 0);
                let labels = data.map(d => d.label);
                let values = data.map(d => d.amount);
                let colors = data.map(d => d.color);
                if (totalIncome > totalExpenses && totalIncome > 0) {
                    labels.push('Unspent Surplus');
                    values.push(totalIncome - totalExpenses);
                    colors.push('#f8fafc');
                }
                expenseChartInstance = new Chart(expenseCtx, {
                    type: 'doughnut',
                    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, hoverOffset: 15 }] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, font: { size: 10, weight: 'black' }, color: '#64748b' } },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 16,
                                cornerRadius: 20,
                                callbacks: {
                                    label: (c) => {
                                        const v = c.parsed;
                                        const t = c.dataset.data.reduce((a, b) => a + b, 0);
                                        return ` ₹${new Intl.NumberFormat('en-IN').format(v)} (${((v/t)*100).toFixed(1)}%)`;
                                    }
                                }
                            }
                        },
                        animation: { animateRotate: true, animateScale: true, duration: 1500 }
                    }
                });
            }

            const userDataEl = document.getElementById('userChartData');
            const userCtx = document.getElementById('userChart');
            if (userCtx && userDataEl) {
                const data = JSON.parse(userDataEl.dataset.chart);
                userChartInstance = new Chart(userCtx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.name),
                        datasets: [
                            { 
                                label: 'Net Savings', 
                                data: data.map(d => d.savings), 
                                backgroundColor: '#10b981', 
                                borderRadius: 12, 
                                barThickness: 35 
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                left: 20,
                                right: 30
                            }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 16,
                                cornerRadius: 16,
                                callbacks: { label: (c) => ` Net Savings: ₹${new Intl.NumberFormat('en-IN').format(c.parsed.x)}` }
                            }
                        },
                        scales: {
                            x: { 
                                grid: { borderDash: [5, 5], color: '#f1f5f9' }, 
                                ticks: { font: { weight: 'bold' }, color: '#94a3b8' } 
                            },
                            y: { 
                                grid: { display: false }, 
                                ticks: { 
                                    font: { weight: 'black', size: 12 }, 
                                    color: '#475569',
                                    padding: 10
                                } 
                            }
                        }
                    }
                });
            }
        }
    </script>

    @if($confirmingTransactionDeletionId)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-sm w-full p-10 space-y-6 border border-white">
                <div class="h-24 w-24 flex items-center justify-center mx-auto rounded-full bg-rose-50 text-rose-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Delete Transaction?</h3>
                    <p class="text-slate-500 mt-2 font-medium">This action is permanent and will adjust your account balances immediately.</p>
                </div>
                <div class="flex gap-4 pt-4">
                    <button wire:click="cancelTransactionDelete" class="flex-1 py-4 bg-slate-100 text-slate-700 rounded-2xl font-black hover:bg-slate-200 transition-all active:scale-95">Cancel</button>
                    <button wire:click="deleteTransaction" class="flex-1 py-4 bg-rose-600 text-white rounded-2xl font-black hover:bg-rose-700 transition-all shadow-xl shadow-rose-200 active:scale-95">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>