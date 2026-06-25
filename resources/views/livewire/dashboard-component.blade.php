<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $confirmingTransactionDeletionId = null;
    public $period = 'current_month';
    public $startDate = null;
    public $endDate = null;

    public function mount()
    {
        $this->updatedPeriod();
    }

    public function updatedPeriod()
    {
        switch ($this->period) {
            case 'current_month':
                $this->startDate = now()->startOfMonth()->toDateString();
                $this->endDate = now()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->startDate = now()->subMonth()->startOfMonth()->toDateString();
                $this->endDate = now()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_quarter':
                $this->startDate = now()->startOfQuarter()->toDateString();
                $this->endDate = now()->endOfQuarter()->toDateString();
                break;
            case 'this_year':
                $this->startDate = now()->startOfYear()->toDateString();
                $this->endDate = now()->endOfYear()->toDateString();
                break;
            case 'all_time':
                $this->startDate = null;
                $this->endDate = null;
                break;
            case 'custom':
                // Keep existing or default to current month if null
                $this->startDate = $this->startDate ?? now()->startOfMonth()->toDateString();
                $this->endDate = $this->endDate ?? now()->endOfMonth()->toDateString();
                break;
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
        return $service->getDashboardData(Auth::id(), $this->startDate, $this->endDate);
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen" x-data="{}" x-init="$watch('showBalances', () => { window.initChart && window.initChart(); })">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Financial Overview</h1>
            <p class="text-slate-500 mt-1">Welcome back, {{ Auth::user()->name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                <select wire:model.live="period" class="bg-transparent border-none text-sm font-bold text-slate-700 focus:ring-0 cursor-pointer">
                    <option value="current_month">Current Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="this_quarter">This Quarter</option>
                    <option value="this_year">This Year</option>
                    <option value="all_time">All Time</option>
                    <option value="custom">Custom Range</option>
                </select>

                @if($period === 'custom')
                    <div class="flex items-center gap-2 px-2 border-l border-slate-200 ml-1">
                        <input type="date" wire:model.live="startDate" class="bg-transparent border-none text-xs font-medium text-slate-600 focus:ring-0 p-0 w-28">
                        <span class="text-slate-400 text-xs">to</span>
                        <input type="date" wire:model.live="endDate" class="bg-transparent border-none text-xs font-medium text-slate-600 focus:ring-0 p-0 w-28">
                    </div>
                @endif
            </div>

            <a href="/transactions/new"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-all shadow-md shadow-indigo-100 flex items-center gap-2 whitespace-nowrap ml-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                New Entry
            </a>
        </div>
    </header>

    <!-- Metrics Grid -->
    <div class="space-y-2">
        <div class="flex items-center justify-between px-2">
            <h2 class="text-lg font-bold text-slate-800">Performance Metrics</h2>
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">
                @if($period === 'all_time')
                    Showing All Time
                @elseif($period === 'custom')
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @else
                    {{ str_replace('_', ' ', ucfirst($period)) }}
                @endif
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-emerald-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Credits</p>
            <p class="text-2xl font-bold text-slate-900 mt-2" x-text="showBalances ? '₹{{ number_format($totalCredits, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-rose-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Debits</p>
            <p class="text-2xl font-bold text-slate-900 mt-2" x-text="showBalances ? '₹{{ number_format($totalDebits, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-indigo-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Net P/L</p>
            <p class="text-2xl font-bold text-slate-900 mt-2"
               :class="showBalances ? '{{ $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}' : 'text-slate-900'"
               x-text="showBalances ? '₹{{ number_format($netProfit, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-cyan-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Wallet Balance</p>
            <p class="text-2xl font-bold text-slate-900 mt-2" x-text="showBalances ? '₹{{ number_format($totalGeneral, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-amber-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Net Worth</p>
            <div class="flex flex-col gap-1 mt-2">
                <p class="text-2xl font-bold text-slate-900" x-text="showBalances ? '₹{{ number_format($netWorth, 2) }}' : '₹ ••••'"></p>
                <div class="space-y-1 mt-1">
                    <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                        <span>Assets: <span x-text="showBalances ? '₹{{ number_format($totalAssets, 2) }}' : '₹ ••••'"></span></span>
                        <span>Debt: <span x-text="showBalances ? '₹{{ number_format($totalLiabilities, 2) }}' : '₹ ••••'"></span></span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] text-emerald-600 font-black uppercase tracking-widest pt-1 border-t border-slate-50">
                        <span>Savings A/C:</span>
                        <span x-text="showBalances ? '₹{{ number_format($totalSavings, 2) }}' : '₹ ••••'"></span>
                    </div>
                </div>
            </div>
        <div class="flex items-center gap-1 mt-3 pt-3 border-t border-slate-50">
                <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></div>
                <span class="text-xs font-bold text-slate-600">
                    <span x-text="showBalances ? '₹{{ number_format($monthlySavings, 2) }}' : '₹ ••••'"></span> saved
                    @if($period === 'current_month') this month
                    @elseif($period === 'last_month') last month
                    @elseif($period === 'this_quarter') this quarter
                    @elseif($period === 'this_year') this year
                    @elseif($period === 'all_time') all time
                    @else in this period
                    @endif
                </span>
            </div>
        </div>
        </div>
    </div>

    <div class="space-y-6">
        <!-- Growth Trends Chart -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 px-2">Growth Trends</h2>
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 min-h-[320px] flex flex-col justify-between">
                <div class="flex-1 relative min-h-[220px]">
                    <canvas id="growthTrendsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Expenses by Category Chart -->
            <div class="space-y-4">
                <h2 class="text-xl font-bold text-slate-900 px-2">Expenses by Category</h2>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 min-h-[350px] flex flex-col sm:flex-row gap-6">
                    @if(count($expenseChart) > 0)
                        <!-- Chart Canvas Container -->
                        <div class="relative w-full sm:w-1/2 flex items-center justify-center min-h-[200px] sm:min-h-0">
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-2">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Debits</span>
                                <span class="text-xl font-black text-slate-800 mt-0.5" x-text="showBalances ? '₹{{ number_format($totalDebits, 2) }}' : '₹ ••••'"></span>
                            </div>
                            <canvas id="expenseTagsChart" class="z-10"></canvas>
                        </div>
                        <!-- Legend and Details List -->
                        <div class="w-full sm:w-1/2 flex flex-col justify-start space-y-2 overflow-y-auto max-h-[300px] pr-1 py-1">
                            @foreach($expenseChart as $item)
                                @php
                                    $percentage = $totalDebits > 0 ? ($item['amount'] / $totalDebits) * 100 : 0;
                                @endphp
                                <a href="/transactions?tagFilter={{ urlencode($item['label']) }}&fromDate={{ $startDate ?? '' }}&toDate={{ $endDate ?? '' }}"
                                   class="flex items-center justify-between p-2.5 hover:bg-slate-50 rounded-xl transition-all group border border-transparent hover:border-slate-100">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="h-3.5 w-3.5 rounded-full flex-shrink-0" style="background-color: {{ $item['color'] ?? '#6366f1' }}"></span>
                                        <span class="text-sm font-bold text-slate-600 group-hover:text-indigo-600 transition-colors truncate">{{ $item['label'] }}</span>
                                    </div>
                                    <div class="text-right flex-shrink-0 pl-2">
                                        <p class="text-sm font-black text-slate-800" x-text="showBalances ? '₹{{ number_format($item['amount'], 2) }}' : '₹ ••••'"></p>
                                        <p class="text-[10px] font-bold text-slate-400 mt-0.5">{{ number_format($percentage, 1) }}%</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="flex-1 flex flex-col items-center justify-center py-12 text-center min-h-[250px]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2-2 4 4m0-7v3h-3" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-slate-400 text-sm font-semibold">No expenses recorded for this period</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Monthly Budgets Tracker -->
            <div class="space-y-4">
                <div class="flex justify-between items-center px-2">
                    <h2 class="text-xl font-bold text-slate-900">Monthly Budgets</h2>
                    <a href="/budgets" class="text-sm font-bold text-indigo-600 hover:text-indigo-700 transition-colors">Manage Budgets &rarr;</a>
                </div>
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 min-h-[350px] flex flex-col justify-start overflow-y-auto max-h-[350px] space-y-4">
                    @if(count($budgets) > 0)
                        @foreach($budgets as $b)
                            @php
                                $b = (object)$b;
                                $percent = $b->amount > 0 ? ($b->spent / $b->amount) * 100 : 0;
                                $clampedPercent = min($percent, 100);
                                $barColor = $percent >= 100 ? 'bg-rose-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                                $textColor = $percent >= 100 ? 'text-rose-600' : ($percent >= 80 ? 'text-amber-600' : 'text-emerald-600');
                            @endphp
                            <div class="space-y-1.5 p-3 hover:bg-slate-50 rounded-2xl border border-transparent hover:border-slate-100 transition-all">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <span class="h-3 w-3 rounded-full flex-shrink-0" style="background-color: {{ $b->color }}"></span>
                                        <span class="text-sm font-bold text-slate-800">{{ $b->tag }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-slate-500">
                                        <span class="text-slate-700" x-text="showBalances ? '₹{{ number_format($b->spent, 2) }}' : '₹ ••••'"></span>
                                        / <span x-text="showBalances ? '₹{{ number_format($b->amount, 2) }}' : '₹ ••••'"></span>
                                    </span>
                                </div>
                                <!-- Progress Bar -->
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="{{ $barColor }} h-full transition-all duration-500" style="width: {{ $clampedPercent }}%"></div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-wider">
                                    <span class="{{ $textColor }}">{{ number_format($percent, 0) }}% Limit</span>
                                    @if($percent >= 100)
                                        <span class="text-rose-600">Exceeded by ₹{{ number_format($b->spent - $b->amount, 2) }}</span>
                                    @else
                                        <span class="text-emerald-600">₹{{ number_format($b->amount - $b->spent, 2) }} Remaining</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="flex-1 flex flex-col items-center justify-center text-center py-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2-2 4 4m0-7v3h-3" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-slate-400 text-sm font-semibold">No active budgets found</span>
                            <a href="/budgets" class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg transition-all">Set budget limit</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let savingsChartInstance = null;
            let expenseChartInstance = null;

            document.addEventListener('livewire:navigated', () => {
                initChart();
            });

            document.addEventListener('DOMContentLoaded', () => {
                initChart();
            });

            function initChart() {
                if (savingsChartInstance) {
                    savingsChartInstance.destroy();
                }

                const ctx = document.getElementById('growthTrendsChart');
                if (ctx) {
                    const data = @json($chartData);

                    savingsChartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.map(d => d.label),
                            datasets: [
                                {
                                    label: 'Income (₹)',
                                    data: data.map(d => d.income),
                                    borderColor: '#10b981',
                                    backgroundColor: '#10b981',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: false,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#10b981',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Expense (₹)',
                                    data: data.map(d => d.expense),
                                    borderColor: '#ef4444',
                                    backgroundColor: '#ef4444',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: false,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#ef4444',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                },
                                {
                                    label: 'Savings (₹)',
                                    data: data.map(d => d.savings),
                                    borderColor: '#6366f1',
                                    backgroundColor: '#6366f1',
                                    borderWidth: 3,
                                    tension: 0.4,
                                    fill: false,
                                    pointBackgroundColor: '#fff',
                                    pointBorderColor: '#6366f1',
                                    pointBorderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        font: {
                                            weight: 'bold',
                                            size: 11
                                        },
                                        color: '#64748b',
                                        boxWidth: 10,
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleFont: { size: 14, weight: 'bold' },
                                    bodyFont: { size: 13 },
                                    padding: 12,
                                    cornerRadius: 12,
                                    callbacks: {
                                        label: function (context) {
                                            let label = context.dataset.label || '';
                                            if (label) label += ': ';
                                            if (context.parsed.y !== null) {
                                                const showBalances = (window.Alpine && document.body) ? Alpine.$data(document.body).showBalances : true;
                                                if (showBalances) {
                                                    label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed.y);
                                                } else {
                                                    label += '₹ ••••';
                                                }
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: '#f8fafc',
                                        drawBorder: false
                                    },
                                    ticks: {
                                        font: { weight: 'bold', size: 9 },
                                        color: '#94a3b8',
                                        callback: function(value) {
                                            const showBalances = (window.Alpine && document.body) ? Alpine.$data(document.body).showBalances : true;
                                            if (!showBalances) return '';
                                            if (value >= 1000) {
                                                return '₹' + (value / 1000) + 'k';
                                            }
                                            return '₹' + value;
                                        }
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        font: { weight: 'bold', size: 10 },
                                        color: '#94a3b8'
                                    }
                                }
                            },
                            animation: {
                                duration: 2000,
                                easing: 'easeOutQuart'
                            }
                        }
                    });
                }

                if (expenseChartInstance) {
                    expenseChartInstance.destroy();
                }

                const ctxExpense = document.getElementById('expenseTagsChart');
                if (ctxExpense) {
                    const expenseData = @json($expenseChart);
                    
                    const percentagePlugin = {
                        id: 'percentageLabels',
                        afterDatasetsDraw(chart) {
                            const { ctx } = chart;
                            ctx.save();
                            chart.data.datasets.forEach((dataset, i) => {
                                const meta = chart.getDatasetMeta(i);
                                if (meta.hidden) return;
                                meta.data.forEach((element, index) => {
                                    const { x, y, startAngle, endAngle, outerRadius, innerRadius } = element;
                                    if (x === undefined || y === undefined || startAngle === undefined || endAngle === undefined || outerRadius === undefined || innerRadius === undefined) {
                                        return;
                                    }
                                    const amount = dataset.data[index];
                                    const total = dataset.data.reduce((acc, curr) => acc + curr, 0);
                                    if (total === 0) return;
                                    const percentage = (amount / total) * 100;
                                    if (percentage < 4) return; // Skip tiny slices to prevent text clutter
                                    
                                    const middleAngle = startAngle + (endAngle - startAngle) / 2;
                                    const radius = innerRadius + (outerRadius - innerRadius) / 2;
                                    const posX = x + Math.cos(middleAngle) * radius;
                                    const posY = y + Math.sin(middleAngle) * radius;
                                    
                                    ctx.fillStyle = '#ffffff';
                                    ctx.font = 'bold 9px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(percentage.toFixed(0) + '%', posX, posY);
                                });
                            });
                            ctx.restore();
                        }
                    };

                    expenseChartInstance = new Chart(ctxExpense, {
                        type: 'doughnut',
                        data: {
                            labels: expenseData.map(d => d.label),
                            datasets: [{
                                data: expenseData.map(d => d.amount),
                                backgroundColor: expenseData.map(d => d.color || '#6366f1'),
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleFont: { size: 13, weight: 'bold' },
                                    bodyFont: { size: 12 },
                                    padding: 10,
                                    cornerRadius: 8,
                                    callbacks: {
                                        label: function (context) {
                                            let label = context.label || '';
                                            if (label) label += ': ';
                                            if (context.parsed !== null) {
                                                const showBalances = (window.Alpine && document.body) ? Alpine.$data(document.body).showBalances : true;
                                                if (showBalances) {
                                                    label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed);
                                                } else {
                                                    label += '₹ ••••';
                                                }
                                            }
                                            return label;
                                        }
                                    }
                                }
                            },
                            cutout: '65%',
                            onClick: (event, elements) => {
                                if (elements && elements.length > 0) {
                                    const index = elements[0].index;
                                    const label = expenseData[index].label;
                                    const fromDate = '{{ $startDate ?? "" }}';
                                    const toDate = '{{ $endDate ?? "" }}';
                                    const url = `/transactions?tagFilter=${encodeURIComponent(label)}&fromDate=${fromDate}&toDate=${toDate}`;
                                    window.location.href = url;
                                }
                            },
                            onHover: (event, chartElement) => {
                                event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                            }
                        },
                        plugins: [percentagePlugin]
                    });
                }
            }

            window.initChart = initChart;
        </script>
    </div>

    <!-- Transaction Deletion Modal -->
    @if($confirmingTransactionDeletionId)
        @teleport('body')
        <div class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
            style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-center; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
            <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 space-y-6"
                style="background-color: white; border-radius: 1.5rem; width: 100%; max-width: 24rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                <div class="h-20 w-20 flex items-center justify-center mx-auto rounded-full"
                    style="background-color: #fff1f2; height: 5rem; width: 5rem; margin-left: auto; margin-right: auto; display: flex; align-items: center; justify-center; border-radius: 9999px;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10"
                        style="color: #e11d48; height: 2.5rem; width: 2.5rem;" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="text-center" style="text-align: center;">
                    <h3 class="text-2xl font-black text-slate-900"
                        style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">Delete Transaction?</h3>
                    <p class="text-slate-500 mt-2 leading-relaxed"
                        style="color: #64748b; margin-top: 0.5rem; line-height: 1.625;">This will permanently remove the
                        record and revert the balance changes in your accounts.</p>
                </div>
                <div class="flex gap-3 pt-2" style="display: flex; gap: 0.75rem; padding-top: 0.5rem;">
                    <button wire:click="cancelTransactionDelete"
                        class="flex-1 py-3.5 bg-slate-100 text-slate-700 rounded-2xl font-bold hover:bg-slate-200 transition-all"
                        style="flex: 1; padding-top: 0.875rem; padding-bottom: 0.875rem; background-color: #f1f5f9; color: #334155; border-radius: 1rem; font-weight: 700; border: none; cursor: pointer;">
                        Cancel
                    </button>
                    <button wire:click="deleteTransaction"
                        class="flex-1 py-3.5 text-white rounded-2xl font-bold hover:opacity-90 transition-all shadow-lg"
                        style="flex: 1; padding-top: 0.875rem; padding-bottom: 0.875rem; background-color: #e11d48; color: white; border-radius: 1rem; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(225, 29, 72, 0.3);">
                        Delete
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>