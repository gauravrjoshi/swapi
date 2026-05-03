<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $confirmingTransactionDeletionId = null;

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
        return $service->getDashboardData(Auth::id());
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen">
    <header class="flex justify-between items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Financial Overview</h1>
            <p class="text-slate-500 mt-1">Welcome back, {{ Auth::user()->name }}</p>
        </div>
        <div class="flex gap-4">
            <a href="/transactions/new"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-semibold transition-all shadow-md shadow-indigo-100 flex items-center gap-2">
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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-emerald-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Credits</p>
            <p class="text-2xl font-bold text-slate-900 mt-2">₹{{ number_format($totalCredits, 2) }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-rose-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Debits</p>
            <p class="text-2xl font-bold text-slate-900 mt-2">₹{{ number_format($totalDebits, 2) }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-indigo-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Net P/L</p>
            <p
                class="text-2xl font-bold text-slate-900 mt-2 {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                ₹{{ number_format($netProfit, 2) }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-amber-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Savings Overview</p>
            <div class="flex flex-col gap-1 mt-2">
                <p class="text-2xl font-bold text-slate-900">₹{{ number_format($totalSavings, 2) }}</p>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">In Savings Accounts</p>
            </div>
            <div class="flex items-center gap-1 mt-3 pt-3 border-t border-slate-50">
                <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></div>
                <span class="text-xs font-bold text-slate-600">₹{{ number_format($monthlySavings, 2) }} saved this
                    month</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Accounts Section -->
        <div class="lg:col-span-1 space-y-4">
            <h2 class="text-xl font-bold text-slate-900 px-2">Your Accounts</h2>
            <div class="space-y-3">
                @forelse($accounts as $account)
                    <div
                        class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex justify-between items-center group hover:border-indigo-200 transition-colors">
                        <div>
                            <p class="font-bold text-slate-800">{{ $account->name }}</p>
                            <p class="text-xs text-slate-400 uppercase tracking-widest mt-1">Balance</p>
                        </div>
                        <p class="text-lg font-black text-slate-900">₹{{ number_format($account->balance, 2) }}</p>
                    </div>
                @empty
                    <div class="bg-slate-100 p-8 rounded-2xl border border-dashed border-slate-300 text-center">
                        <p class="text-slate-500">No accounts found.</p>
                        <a href="/accounts/new" class="text-indigo-600 font-bold text-sm mt-2 block">Create first
                            account</a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Monthly Savings Chart -->
        <div class="lg:col-span-2 space-y-4">
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