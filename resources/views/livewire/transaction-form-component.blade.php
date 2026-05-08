<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Tag;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;

new class extends Component {
    public $entries = [];
    public $tagSearch = '';
    public $transactionId = null;
    public $isModal = false;

    public function mount($transaction = null, $isModal = false)
    {
        $this->isModal = filter_var($isModal, FILTER_VALIDATE_BOOLEAN);

        if ($transaction) {
            // Check if user can manage this transaction
            if (!$transaction->canBeManagedBy(Auth::id())) {
                abort(403, 'You do not have permission to edit this transaction.');
            }

            $this->transactionId = $transaction->id;

            $entry = [
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'date' => $transaction->date->format('Y-m-d'),
                'description' => $transaction->description ?? $transaction->transaction_details,
                'tag' => $transaction->tag,
                'account_id' => '',
                'from_account_id' => '',
                'to_account_id' => ''
            ];

            if ($transaction->type === 'transfer') {
                $entry['from_account_id'] = $transaction->from_account_id;
                $entry['to_account_id'] = $transaction->to_account_id;
            } else {
                $entry['account_id'] = $transaction->account_id;
            }

            $this->entries[] = $entry;
        } else {
            $this->addEntry();
        }
    }

    #[On('edit-transaction')]
    public function loadTransaction($id)
    {
        if (is_array($id) && isset($id['id'])) {
            $id = $id['id'];
        }

        $transaction = Transaction::find($id);

        if ($transaction) {
            if (!$transaction->canBeManagedBy(Auth::id())) {
                $this->addError('general', 'You do not have permission to edit this transaction.');
                return;
            }

            $this->transactionId = $transaction->id;

            $entry = [
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'date' => $transaction->date->format('Y-m-d'),
                'description' => $transaction->description ?? $transaction->transaction_details,
                'tag' => $transaction->tag,
                'account_id' => '',
                'from_account_id' => '',
                'to_account_id' => ''
            ];

            if ($transaction->type === 'transfer') {
                $entry['from_account_id'] = $transaction->from_account_id;
                $entry['to_account_id'] = $transaction->to_account_id;
            } else {
                $entry['account_id'] = $transaction->account_id;
            }

            $this->entries = [$entry];
            $this->resetErrorBag();
        }
    }

    #[On('new-transaction')]
    public function resetForm()
    {
        $this->transactionId = null;
        $this->entries = [];
        $this->addEntry();
        $this->resetErrorBag();
    }

    public function addEntry()
    {
        $this->entries[] = [
            'type' => 'debit',
            'amount' => '',
            'account_id' => Account::where('user_id', Auth::id())->first()?->id ?? '',
            'from_account_id' => '',
            'to_account_id' => '',
            'description' => '',
            'tag' => '',
            'date' => now()->format('Y-m-d'),
        ];
    }

    public function removeEntry($index)
    {
        if (count($this->entries) > 1) {
            unset($this->entries[$index]);
            $this->entries = array_values($this->entries); // Re-index array
        }
    }

    public function selectTag($tagName, $index)
    {
        $this->entries[$index]['tag'] = $tagName;
        $this->tagSearch = '';
    }

    public function save(TransactionService $service)
    {
        $rules = [
            'entries.*.type' => 'required|in:credit,debit,transfer',
            'entries.*.amount' => 'required|numeric|min:0.01',
            'entries.*.date' => 'required|date',
            'entries.*.description' => 'nullable|string|max:255',
            'entries.*.tag' => 'nullable|string|max:50',
            'entries.*.account_id' => 'nullable|exists:accounts,id',
            'entries.*.from_account_id' => 'nullable|exists:accounts,id',
            'entries.*.to_account_id' => 'nullable|exists:accounts,id|different:entries.*.from_account_id',
        ];

        $this->validate($rules);

        // Custom validation logic per entry
        foreach ($this->entries as $index => $entry) {
            if ($entry['type'] === 'transfer') {
                if (empty($entry['from_account_id'])) {
                    $this->addError("entries.{$index}.from_account_id", 'The source account is required.');
                    return;
                }
                if (empty($entry['to_account_id'])) {
                    $this->addError("entries.{$index}.to_account_id", 'The destination account is required.');
                    return;
                }
                $fromAccount = Account::find($entry['from_account_id']);
                $toAccount = Account::find($entry['to_account_id']);
                if ($fromAccount->user_id != Auth::id() && $toAccount->user_id !== Auth::id()) {
                    $this->addError("entries.{$index}.from_account_id", 'You must own at least one of the accounts in a transfer.');
                    return;
                }
            } else {
                if (empty($entry['account_id'])) {
                    $this->addError("entries.{$index}.account_id", 'The account is required.');
                    return;
                }
                $account = Account::find($entry['account_id']);
                if ($account->user_id != Auth::id()) {
                    $this->addError("entries.{$index}.account_id", 'You can only add transactions to your own accounts.');
                    return;
                }
            }
        }

        $entriesToSave = array_map(function ($entry) {
            foreach ($entry as $key => $value) {
                if ($value === '') {
                    $entry[$key] = null;
                }
            }
            if ($entry['type'] !== 'transfer') {
                $entry['from_account_id'] = null;
                $entry['to_account_id'] = null;
            } else {
                $entry['account_id'] = null;
            }
            return $entry;
        }, $this->entries);

        if ($this->transactionId) {
            $transaction = Transaction::findOrFail($this->transactionId);
            $service->updateTransaction($transaction, $entriesToSave[0]);
            session()->flash('message', 'Transaction updated successfully.');
        } else {
            foreach ($entriesToSave as $entry) {
                $service->createTransaction($entry);
            }
            session()->flash('message', count($entriesToSave) > 1 ? count($entriesToSave) . ' transactions recorded successfully.' : 'Transaction recorded successfully.');
        }

        if ($this->isModal) {
            $this->dispatch('transaction-saved');

            // Reset to a clean state after save
            $this->entries = [];
            $this->addEntry();
        } else {
            return redirect()->to('/dashboard');
        }
    }

    public function with()
    {
        $tagQuery = Tag::orderBy('name');

        if ($this->tagSearch) {
            $tagQuery->where('name', 'like', '%' . trim($this->tagSearch) . '%');
        }

        return [
            // 'accounts' => Account::where('user_id', Auth::id())->orderBy('name')->get(),
            'accounts' => Account::orderBy('name')->get(),
            'tags' => $tagQuery->get(),
        ];
    }
};
?>

<div class="p-6 w-full mx-auto">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            border: 1px solid #f8fafc;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <div class="bg-white rounded-[2.5rem] shadow-2xl border border-slate-100 overflow-hidden">
        <!-- Brand-Matching Gradient Header -->
        <header class="bg-gradient-to-r from-[#ed760e] to-[#56b6e9] px-10 py-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 opacity-15 transform translate-x-1/4 -translate-y-1/4">
                <svg width="240" height="240" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="100" cy="100" r="100" fill="white" />
                </svg>
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-white tracking-tight">
                        {{ $transactionId ? 'Update' : 'New' }} Transaction
                    </h2>
                    <p class="text-white/90 text-sm font-medium mt-1">
                        {{ $transactionId ? 'Refining your financial records' : 'Capturing your latest financial activity' }}
                    </p>
                </div>
                <div
                    class="h-12 w-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </header>

        <form wire:submit.prevent="save" class="p-10 space-y-8">
            @if (session()->has('message'))
                <div
                    class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl font-bold border border-emerald-100 flex items-center gap-3 animate-bounce">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('message') }}
                </div>
            @endif

            <div class="space-y-6">
                @foreach($entries as $index => $entry)
                    <div x-data="{ currentType: @entangle('entries.' . $index . '.type').live }"
                        class="p-6 bg-slate-50/50 rounded-3xl border border-slate-200 relative shadow-sm transition-all hover:shadow-md">

                        @if(count($entries) > 1 && !$transactionId)
                            <button type="button" wire:click="removeEntry({{ $index }})"
                                class="absolute top-6 right-6 h-8 w-8 rounded-full bg-rose-50 text-rose-500 hover:bg-rose-100 transition-colors flex items-center justify-center z-10"
                                title="Remove Entry">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @endif

                        <!-- Premium Type Selector with Logo Colors -->
                        <div
                            class="flex p-1.5 bg-white rounded-[1.25rem] w-full md:w-fit mb-6 shadow-sm border border-slate-100">
                            <button type="button" @click="currentType = 'debit'"
                                class="px-8 py-2.5 rounded-[1rem] text-sm font-black transition-all flex items-center gap-2"
                                :class="currentType === 'debit' ? 'bg-slate-100 text-[#ed760e] shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <span class="h-2 w-2 rounded-full"
                                    :class="currentType === 'debit' ? 'bg-[#ed760e]' : 'bg-slate-300'"></span>
                                Expense
                            </button>
                            <button type="button" @click="currentType = 'credit'"
                                class="px-8 py-2.5 rounded-[1rem] text-sm font-black transition-all flex items-center gap-2"
                                :class="currentType === 'credit' ? 'bg-slate-100 text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <span class="h-2 w-2 rounded-full"
                                    :class="currentType === 'credit' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                Income
                            </button>
                            <button type="button" @click="currentType = 'transfer'"
                                class="px-8 py-2.5 rounded-[1rem] text-sm font-black transition-all flex items-center gap-2"
                                :class="currentType === 'transfer' ? 'bg-slate-100 text-[#56b6e9] shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <span class="h-2 w-2 rounded-full"
                                    :class="currentType === 'transfer' ? 'bg-[#56b6e9]' : 'bg-slate-300'"></span>
                                Transfer
                            </button>
                        </div>

                        <div class="grid grid-cols-12 gap-8 items-end mb-6">
                            <!-- Amount -->
                            <div class="col-span-12 md:col-span-3 space-y-2.5">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Amount <span
                                        class="text-rose-500">*</span></label>
                                <div class="group relative">
                                    <span
                                        class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-lg transition-colors"
                                        :class="{
                                                        'text-rose-400': currentType === 'debit',
                                                        'text-emerald-400': currentType === 'credit',
                                                        'text-indigo-400': currentType === 'transfer'
                                                    }">₹</span>
                                    <input type="number" step="0.01" wire:model="entries.{{ $index }}.amount" required
                                        class="w-full pl-10 pr-4 py-4 bg-white border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 transition-all font-black text-lg shadow-sm"
                                        :class="{
                                                        'text-rose-600 focus:ring-rose-50 focus:border-rose-500': currentType === 'debit',
                                                        'text-emerald-600 focus:ring-emerald-50 focus:border-emerald-500': currentType === 'credit',
                                                        'text-indigo-600 focus:ring-indigo-50 focus:border-indigo-500': currentType === 'transfer'
                                                    }" placeholder="0.00">
                                </div>
                                @error("entries.$index.amount") <span
                                class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Tag -->
                            <div class="col-span-12 md:col-span-3 space-y-2.5">
                                <label
                                    class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Category</label>
                                <div class="relative" x-data="{ 
                                                    open: false,
                                                    focusSearch() {
                                                        $nextTick(() => {
                                                            if (this.open && this.$refs.searchInput) {
                                                                this.$refs.searchInput.focus();
                                                            }
                                                        });
                                                    }
                                                 }" x-effect="if(open) focusSearch()" @click.away="open = false">
                                    <button type="button" @click="open = !open"
                                        class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-50 transition-all cursor-pointer flex justify-between items-center text-base shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" />
                                            </svg>
                                            <span
                                                class="{{ $entries[$index]['tag'] ? 'text-slate-900 font-black' : 'text-slate-400 font-bold' }}">
                                                {{ $entries[$index]['tag'] ?: 'Select category' }}
                                            </span>
                                        </div>
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 text-slate-400 transition-transform"
                                            :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>

                                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="transform opacity-0 -translate-y-4"
                                        x-transition:enter-end="transform opacity-100 translate-y-0"
                                        class="absolute z-50 mt-3 w-full left-0 bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden">
                                        <div class="p-4 border-b border-slate-50 bg-slate-50/50">
                                            <div
                                                class="flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-2xl focus-within:ring-4 focus-within:ring-indigo-50 transition-all">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-5 w-5 text-slate-400 flex-shrink-0" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                                <input type="text" wire:model.live.debounce.300ms="tagSearch"
                                                    wire:key="tag-search-input-{{ $index }}" x-ref="searchInput"
                                                    @click.stop="" @keydown.enter.prevent=""
                                                    placeholder="Search categories..."
                                                    class="w-full border-none focus:ring-0 p-0 text-sm text-slate-900 placeholder:text-slate-400 bg-transparent font-black">
                                            </div>
                                        </div>
                                        <div
                                            class="max-h-60 overflow-y-auto p-3 space-y-1.5 custom-scrollbar scroll-smooth">
                                            <button type="button" wire:click="selectTag('', {{ $index }})"
                                                @click="open = false"
                                                class="w-full text-left px-4 py-3 rounded-xl text-xs font-black text-slate-400 hover:bg-slate-50 hover:text-slate-600 cursor-pointer transition-all flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Clear selection
                                            </button>
                                            @forelse($tags as $t)
                                                <button type="button" wire:key="tag-item-{{ $index }}-{{ $t->id }}"
                                                    wire:click.stop="selectTag('{{ addslashes($t->name) }}', {{ $index }})"
                                                    @click="open = false"
                                                    class="w-full text-left px-4 py-3.5 rounded-2xl text-sm font-black flex items-center justify-between transition-all cursor-pointer {{ $entries[$index]['tag'] === $t->name ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-700 hover:bg-slate-50' }}">
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            style="height: 12px; width: 12px; border-radius: 9999px; background-color: {{ $t->color }}; box-shadow: 0 0 10px {{ $t->color }}60;">
                                                        </div>
                                                        {{ $t->name }}
                                                    </div>
                                                </button>
                                            @empty
                                                <div
                                                    class="px-4 py-10 text-center bg-slate-50/50 rounded-3xl border border-dashed border-slate-200 mt-2">
                                                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">No
                                                        matching
                                                        categories</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                                @error("entries.$index.tag") <span
                                class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Date -->
                            <div class="col-span-12 md:col-span-2 space-y-2.5">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Date <span
                                        class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input type="date" wire:model="entries.{{ $index }}.date" required
                                        class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all text-slate-900 font-black text-sm shadow-sm">
                                </div>
                                @error("entries.$index.date") <span
                                class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span> @enderror
                            </div>

                            <template x-if="currentType === 'transfer'">
                                <!-- From Account -->
                                <div class="col-span-12 md:col-span-2 space-y-2.5">
                                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Source
                                        <span class="text-rose-500">*</span></label>
                                    <select wire:model="entries.{{ $index }}.from_account_id"
                                        class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-sm appearance-none">
                                        <option value="">Choose source</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}
                                                (₹{{ number_format($account->balance, 2) }})</option>
                                        @endforeach
                                    </select>
                                    @error("entries.$index.from_account_id") <span
                                        class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </template>

                            <template x-if="currentType === 'transfer'">
                                <!-- To Account -->
                                <div class="col-span-12 md:col-span-2 space-y-2.5">
                                    <label
                                        class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Destination
                                        <span class="text-rose-500">*</span></label>
                                    <select wire:model="entries.{{ $index }}.to_account_id"
                                        class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-sm appearance-none">
                                        <option value="">Choose destination</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }}
                                                (₹{{ number_format($account->balance, 2) }})</option>
                                        @endforeach
                                    </select>
                                    @error("entries.$index.to_account_id") <span
                                        class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </template>

                            <template x-if="currentType !== 'transfer'">
                                <!-- Single Account -->
                                <div class="col-span-12 md:col-span-4 space-y-2.5">
                                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Account
                                        <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <select wire:model="entries.{{ $index }}.account_id"
                                            class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-sm appearance-none">
                                            <option value="">Select an account</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}
                                                    (₹{{ number_format($account->balance, 2) }})</option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    @error("entries.$index.account_id") <span
                                        class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </template>
                        </div>

                        <div class="grid grid-cols-12 gap-8 items-end">
                            <!-- Description -->
                            <div class="col-span-12 md:col-span-8 space-y-2.5">
                                <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Description
                                    /
                                    Memo</label>
                                <div class="group relative">
                                    <input type="text" wire:model="entries.{{ $index }}.description"
                                        class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-sm"
                                        placeholder="Add a note to this transaction...">
                                </div>
                                @error("entries.$index.description") <span
                                    class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!$transactionId)
                <div class="flex justify-center mt-4">
                    <button type="button" wire:click="addEntry"
                        class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-indigo-50 text-indigo-600 font-bold hover:bg-indigo-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Add Another Entry
                    </button>
                </div>
            @endif

            <div class="pt-8 flex justify-end items-center gap-6 border-t border-slate-100">
                @if(!$isModal)
                    <a href="/dashboard"
                        class="px-10 py-4 rounded-2xl font-black text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all text-center">
                        Cancel
                    </a>
                @else
                    <button type="button" @click="$dispatch('close-modal')"
                        class="px-10 py-4 rounded-2xl font-black text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all text-center">
                        Cancel
                    </button>
                @endif
                <button type="submit"
                    class="px-16 py-4 rounded-2xl font-black text-white bg-[#ed760e] hover:bg-[#d8690d] transition-all shadow-2xl shadow-orange-200 transform active:scale-95 text-lg">
                    {{ $transactionId ? 'Update Record' : 'Save ' . (count($entries) > 1 ? 'Records' : 'Record') }}
                </button>
            </div>
        </form>
    </div>
</div>>
</div>