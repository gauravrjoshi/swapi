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

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen">
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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

    <div class="grid grid-cols-1 gap-6">
        <!-- Monthly Savings Chart -->
        <div class="space-y-4">
            <h2 class="text-xl font-bold text-slate-900 px-2">Savings Growth</h2>
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 min-h-[250px] flex flex-col">
                <div class="flex-1 relative">
                    <canvas id="savingsChart"></canvas>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('livewire:navigated', () => {
                initChart();
            });

            document.addEventListener('DOMContentLoaded', () => {
                initChart();
            });

            function initChart() {
                const ctx = document.getElementById('savingsChart');
                if (!ctx) return;

                const data = @json($chartData);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(d => d.label),
                        datasets: [{
                            label: 'Net Savings (₹)',
                            data: data.map(d => d.savings),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 4,
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#6366f1',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8,
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
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                padding: 12,
                                cornerRadius: 12,
                                callbacks: {
                                    label: function (context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                display: false,
                                grid: { display: false }
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