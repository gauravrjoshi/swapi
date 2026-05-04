<?php

use Livewire\Volt\Component;
use App\Models\Transaction;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    public $userFilter = '';
    public $fromDate = '';
    public $toDate = '';
    public $confirmingTransactionDeletionId = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function updatingFromDate()
    {
        $this->resetPage();
    }

    public function updatingToDate()
    {
        $this->resetPage();
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
        
        if (!$transaction->canBeManagedBy(Auth::id())) {
            $this->confirmingTransactionDeletionId = null;
            session()->flash('message', 'You do not have permission to delete this transaction.');
            return;
        }

        $service->deleteTransaction($transaction);

        $this->confirmingTransactionDeletionId = null;
        session()->flash('message', 'Transaction deleted successfully.');
    }

    public function with(App\Services\TransactionService $service)
    {
        return [
            'transactions' => $service->getTransactions(auth()->id(), [
                'search' => $this->search,
                'type' => $this->typeFilter,
                'user_id' => $this->userFilter,
                'from_date' => $this->fromDate,
                'to_date' => $this->toDate,
            ], 20),
            'users' => \App\Models\User::all(),
        ];
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6">
    <header class="flex justify-between items-center bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">All Transactions</h1>
            <p class="text-slate-500 mt-1">View and manage every financial record in the system</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ route('transactions.download', ['search' => $search, 'type' => $typeFilter, 'user' => $userFilter, 'from' => $fromDate, 'to' => $toDate]) }}"
                class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-2xl font-bold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Download PDF
            </a>
            <a href="/transactions/new"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-bold transition-all shadow-lg shadow-indigo-100 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                        clip-rule="evenodd" />
                </svg>
                New Entry
            </a>
        </div>
    </header>

    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Filters Bar -->
        <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex flex-col md:flex-row gap-4 items-center">
            <div class="relative flex-1">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by description, tag or details..."
                    class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-medium">
            </div>

            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <div
                    class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2 shadow-inner">
                    <span class="text-[10px] font-black text-slate-400 uppercase">From</span>
                    <input type="date" wire:model.live="fromDate"
                        class="border-none p-0 text-sm font-bold focus:ring-0 cursor-pointer">
                </div>

                <div
                    class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 py-2 shadow-inner">
                    <span class="text-[10px] font-black text-slate-400 uppercase">To</span>
                    <input type="date" wire:model.live="toDate"
                        class="border-none p-0 text-sm font-bold focus:ring-0 cursor-pointer">
                </div>

                <select wire:model.live="typeFilter"
                    class="bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-50 transition-all outline-none">
                    <option value="">All Types</option>
                    <option value="credit">Income (Credit)</option>
                    <option value="debit">Expense (Debit)</option>
                    <option value="transfer">Transfer</option>
                </select>

                <select wire:model.live="userFilter"
                    class="bg-white border border-slate-200 rounded-2xl px-4 py-3 text-sm font-bold focus:ring-4 focus:ring-indigo-50 transition-all outline-none">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>

                @if($fromDate || $toDate || $typeFilter || $userFilter || $search)
                    <button wire:click="$reset(['fromDate', 'toDate', 'typeFilter', 'userFilter', 'search'])"
                        class="px-4 py-3 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-all text-xs uppercase tracking-widest">
                        Reset
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Description</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Account
                        </th>
                        <th
                            class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                            Amount</th>
                        <th
                            class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transactions as $tx)
                        <tr class="group hover:bg-slate-50/80 transition-all">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <p class="text-sm font-bold text-slate-900">{{ $tx->date->format('d M, Y') }}</p>
                                <p class="text-[10px] text-slate-400 font-medium uppercase mt-0.5">
                                    {{ $tx->user?->name ?? 'System' }}
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                <p class="text-sm font-bold text-slate-800">
                                    {{ $tx->description ?? ($tx->transaction_details ?? 'Unspecified') }}
                                </p>
                            </td>
                            <td class="px-6 py-5">
                                @if($tx->tag)
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-tight bg-indigo-50 text-indigo-600">
                                        {{ $tx->tag }}
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2 w-2 rounded-full {{ $tx->type === 'credit' ? 'bg-emerald-500' : ($tx->type === 'debit' ? 'bg-rose-500' : 'bg-indigo-500') }}">
                                    </div>
                                    <span class="text-xs font-bold text-slate-600">
                                        @if($tx->type === 'transfer')
                                            {{ $tx->fromAccount?->name }} &rarr; {{ $tx->toAccount?->name }}
                                        @else
                                            {{ $tx->mainAccount?->name ?? 'Default' }}
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-right whitespace-nowrap">
                                <span class="text-sm font-black 
                                                {{ $tx->type === 'credit' ? 'text-emerald-600' : '' }}
                                                {{ $tx->type === 'debit' ? 'text-rose-600' : '' }}
                                                {{ $tx->type === 'transfer' ? 'text-indigo-600' : '' }}">
                                    {{ $tx->type === 'debit' ? '-' : '' }}₹{{ number_format($tx->amount, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right whitespace-nowrap">
                                <div
                                    class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                    @if($tx->canBeManagedBy(Auth::id()))
                                        <a href="/transactions/{{ $tx->id }}/edit"
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-white rounded-xl transition-all shadow-sm" title="Edit Transaction">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                        <button wire:click="confirmTransactionDelete({{ $tx->id }})"
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-white rounded-xl transition-all shadow-sm" title="Delete Transaction">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @else
                                        <div class="p-2 text-slate-300" title="Read-only: You do not own this account">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">No transactions
                                        found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-6 border-t border-slate-50 bg-slate-50/30">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Delete Modal -->
    @if($confirmingTransactionDeletionId)
        <div class="fixed inset-0 z-[99999] flex items-center justify-center p-4"
            style="background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
            <div
                class="bg-white rounded-[2.5rem] shadow-2xl max-w-sm w-full p-10 space-y-8 animate-in fade-in zoom-in duration-200">
                <div class="h-24 w-24 flex items-center justify-center mx-auto rounded-full bg-rose-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-rose-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-3xl font-black text-slate-900">Are you sure?</h3>
                    <p class="text-slate-500 mt-4 leading-relaxed font-medium">This transaction will be permanently deleted
                        and account balances will be adjusted.</p>
                </div>
                <div class="flex gap-4">
                    <button wire:click="cancelTransactionDelete"
                        class="flex-1 py-4 bg-slate-100 text-slate-700 rounded-2xl font-black hover:bg-slate-200 transition-all active:scale-95">
                        Cancel
                    </button>
                    <button wire:click="deleteTransaction"
                        class="flex-1 py-4 bg-rose-600 text-white rounded-2xl font-black hover:bg-rose-700 transition-all shadow-xl shadow-rose-200 active:scale-95">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('message'))
        <div
            class="fixed bottom-8 right-8 z-[100] bg-emerald-600 text-white px-8 py-4 rounded-2xl font-black shadow-2xl animate-in slide-in-from-bottom duration-300 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('message') }}
        </div>
    @endif
</div>