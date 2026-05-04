<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Models\Tag;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component {
    public $type = 'debit';
    public $amount;
    public $account_id;
    public $from_account_id;
    public $to_account_id;
    public $description;
    public $tag;
    public $tagSearch = '';
    public $date;
    public $transactionId = null;

    public function mount($transaction = null)
    {
        if ($transaction) {
            // Check if user can manage this transaction
            if (!$transaction->canBeManagedBy(Auth::id())) {
                abort(403, 'You do not have permission to edit this transaction.');
            }

            $this->transactionId = $transaction->id;
            $this->type = $transaction->type;
            $this->amount = $transaction->amount;
            $this->date = $transaction->date->format('Y-m-d');
            $this->description = $transaction->description;
            $this->tag = $transaction->tag;

            if ($this->type === 'transfer') {
                $this->from_account_id = $transaction->from_account_id;
                $this->to_account_id = $transaction->to_account_id;
            } else {
                $this->account_id = $transaction->account_id;
            }
        } else {
            $this->date = now()->format('Y-m-d');
            // Default to first user account if available
            $this->account_id = Account::where('user_id', Auth::id())->first()?->id;
        }
    }

    public function selectTag($tagName)
    {
        $this->tag = $tagName;
        $this->tagSearch = '';
    }

    public function save(TransactionService $service)
    {
        $rules = [
            'type' => 'required|in:credit,debit,transfer',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:50',
        ];

        if ($this->type === 'transfer') {
            $rules['from_account_id'] = [
                'required',
                Rule::exists('accounts', 'id'),
            ];
            $rules['to_account_id'] = [
                'required',
                'different:from_account_id',
                Rule::exists('accounts', 'id'),
            ];
        } else {
            $rules['account_id'] = [
                'required',
                Rule::exists('accounts', 'id'),
            ];
        }

        $validated = $this->validate($rules);

        // Ownership Verification
        if ($this->type === 'transfer') {
            $fromAccount = Account::find($this->from_account_id);
            $toAccount = Account::find($this->to_account_id);
            if ($fromAccount->user_id !== Auth::id() && $toAccount->user_id !== Auth::id()) {
                $this->addError('from_account_id', 'You must own at least one of the accounts in a transfer.');
                return;
            }
        } else {
            $account = Account::find($this->account_id);
            if ($account->user_id !== Auth::id()) {
                $this->addError('account_id', 'You can only add transactions to your own accounts.');
                return;
            }
        }

        $validated = $this->validate($rules);

        if ($this->transactionId) {
            $transaction = Transaction::findOrFail($this->transactionId);
            $service->updateTransaction($transaction, $validated);
            session()->flash('message', 'Transaction updated successfully.');
        } else {
            $service->createTransaction($validated);
            session()->flash('message', 'Transaction recorded successfully.');
        }

        return redirect()->to('/dashboard');
    }

    public function with()
    {
        $tagQuery = Tag::orderBy('name');

        if ($this->tagSearch) {
            $tagQuery->where('name', 'like', '%' . trim($this->tagSearch) . '%');
        }

        return [
            'accounts' => Account::where('user_id', Auth::id())->orderBy('name')->get(),
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

        <form wire:submit.prevent="save" class="p-10 space-y-8" x-data="{ currentType: @entangle('type').live }">
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

            <!-- Premium Type Selector with Logo Colors -->
            <div class="flex p-1.5 bg-slate-100/80 rounded-[1.25rem] w-full md:w-fit">
                <button type="button" @click="currentType = 'debit'"
                    class="px-8 py-2.5 rounded-[1rem] text-sm font-black transition-all flex items-center gap-2"
                    :class="currentType === 'debit' ? 'bg-white text-[#ed760e] shadow-lg' : 'text-slate-500 hover:text-slate-700'">
                    <span class="h-2 w-2 rounded-full"
                        :class="currentType === 'debit' ? 'bg-[#ed760e]' : 'bg-slate-300'"></span>
                    Expense
                </button>
                <button type="button" @click="currentType = 'credit'"
                    class="px-8 py-2.5 rounded-[1rem] text-sm font-black transition-all flex items-center gap-2"
                    :class="currentType === 'credit' ? 'bg-white text-emerald-600 shadow-lg' : 'text-slate-500 hover:text-slate-700'">
                    <span class="h-2 w-2 rounded-full"
                        :class="currentType === 'credit' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                    Income
                </button>
                <button type="button" @click="currentType = 'transfer'"
                    class="px-8 py-2.5 rounded-[1rem] text-sm font-black transition-all flex items-center gap-2"
                    :class="currentType === 'transfer' ? 'bg-white text-[#56b6e9] shadow-lg' : 'text-slate-500 hover:text-slate-700'">
                    <span class="h-2 w-2 rounded-full"
                        :class="currentType === 'transfer' ? 'bg-[#56b6e9]' : 'bg-slate-300'"></span>
                    Transfer
                </button>
            </div>

            <div class="grid grid-cols-12 gap-8 items-end">
                <!-- Amount -->
                <div class="col-span-12 md:col-span-2 space-y-2.5">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Amount <span class="text-rose-500">*</span></label>
                    <div class="group relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-lg transition-colors"
                            :class="{
                                'text-rose-400': currentType === 'debit',
                                'text-emerald-400': currentType === 'credit',
                                'text-indigo-400': currentType === 'transfer'
                            }">₹</span>
                        <input type="number" step="0.01" wire:model="amount" required
                            class="w-full pl-10 pr-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 transition-all font-black text-lg shadow-inner"
                            :class="{
                                'text-rose-600 focus:ring-rose-50 focus:border-rose-500': currentType === 'debit',
                                'text-emerald-600 focus:ring-emerald-50 focus:border-emerald-500': currentType === 'credit',
                                'text-indigo-600 focus:ring-indigo-50 focus:border-indigo-500': currentType === 'transfer'
                            }" placeholder="0.00">
                    </div>
                    @error('amount') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span> @enderror
                </div>

                <!-- Tag -->
                <div class="col-span-12 md:col-span-3 space-y-2.5">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Category</label>
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
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-50 transition-all cursor-pointer flex justify-between items-center text-base shadow-inner">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 11h.01M7 15h.01M13 7h.01M13 11h.01M13 15h.01M17 7h.01M17 11h.01M17 15h.01" />
                                </svg>
                                <span class="{{ $tag ? 'text-slate-900 font-black' : 'text-slate-400 font-bold' }}">
                                    {{ $tag ?: 'Select category' }}
                                </span>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 transition-transform"
                                :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400 flex-shrink-0"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <input type="text" wire:model.live.debounce.300ms="tagSearch"
                                        wire:key="tag-search-input" x-ref="searchInput" @click.stop=""
                                        @keydown.enter.prevent="" placeholder="Search categories..."
                                        class="w-full border-none focus:ring-0 p-0 text-sm text-slate-900 placeholder:text-slate-400 bg-transparent font-black">
                                </div>
                            </div>
                            <div class="max-h-60 overflow-y-auto p-3 space-y-1.5 custom-scrollbar scroll-smooth">
                                <button type="button" wire:click="selectTag('')" @click="open = false"
                                    class="w-full text-left px-4 py-3 rounded-xl text-xs font-black text-slate-400 hover:bg-slate-50 hover:text-slate-600 cursor-pointer transition-all flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Clear selection
                                </button>
                                @forelse($tags as $t)
                                    <button type="button" wire:key="tag-item-{{ $t->id }}"
                                        wire:click.stop="selectTag('{{ addslashes($t->name) }}')" @click="open = false"
                                        class="w-full text-left px-4 py-3.5 rounded-2xl text-sm font-black flex items-center justify-between transition-all cursor-pointer {{ $tag === $t->name ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-700 hover:bg-slate-50' }}">
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
                                        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">No matching
                                            categories</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @error('tag') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span> @enderror
                </div>

                <!-- Date -->
                <div class="col-span-12 md:col-span-2 space-y-2.5">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Date <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="date" wire:model="date" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all text-slate-900 font-black text-sm shadow-inner">
                    </div>
                    @error('date') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span> @enderror
                </div>

                @if($type === 'transfer')
                    <!-- From Account -->
                    <div class="col-span-12 md:col-span-2 space-y-2.5">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Source <span class="text-rose-500">*</span></label>
                        <select wire:model="from_account_id" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-inner appearance-none">
                            <option value="">Choose source</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}
                                    (₹{{ number_format($account->balance, 2) }})</option>
                            @endforeach
                        </select>
                        @error('from_account_id') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- To Account -->
                    <div class="col-span-12 md:col-span-3 space-y-2.5">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Destination <span class="text-rose-500">*</span></label>
                        <select wire:model="to_account_id" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-inner appearance-none">
                            <option value="">Choose destination</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}
                                    (₹{{ number_format($account->balance, 2) }})</option>
                            @endforeach
                        </select>
                        @error('to_account_id') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                        @enderror
                    </div>
                @else
                    <!-- Single Account -->
                    <div class="col-span-12 md:col-span-5 space-y-2.5">
                        <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Account <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select wire:model="account_id" required
                                class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-inner appearance-none">
                                <option value="">Select an account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}
                                        (₹{{ number_format($account->balance, 2) }})</option>
                                @endforeach
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        @error('account_id') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-12 gap-8 items-end">
                <!-- Description -->
                <div class="col-span-12 md:col-span-8 space-y-2.5">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Description /
                        Memo</label>
                    <div class="group relative">
                        <input type="text" wire:model="description"
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all font-black text-slate-900 text-sm shadow-inner"
                            placeholder="Add a note to this transaction...">
                    </div>
                    @error('description') <span class="text-rose-500 text-xs font-bold px-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Logged in User -->
                <div class="col-span-12 md:col-span-4 space-y-2.5">
                    <label class="text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Identity</label>
                    <div
                        class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-between shadow-inner">
                        <div class="flex items-center gap-3">
                            <div
                                class="h-8 w-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-xs">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <span class="text-sm font-black text-slate-700">{{ Auth::user()->name }}</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-[10px] font-black uppercase tracking-tighter">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Verified
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-12 flex justify-end items-center gap-6">
                <a href="/dashboard"
                    class="px-10 py-4 rounded-2xl font-black text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all text-center">
                    Cancel
                </a>
                <button type="submit"
                    class="px-16 py-4 rounded-2xl font-black text-white bg-[#ed760e] hover:bg-[#d8690d] transition-all shadow-2xl shadow-orange-200 transform active:scale-95 text-lg">
                    {{ $transactionId ? 'Update Record' : 'Save' }}
                </button>
            </div>
        </form>
    </div>
</div>