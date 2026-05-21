<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    public ?int $accountId = null;
    public $name;
    public $initial_balance = 0;
    public $account_type = 'savings';
    public $bank_name;
    public $account_holder_name;
    public $account_number;
    public $ifsc_code;
    public $branch_address;
    public $balance;

    public bool $isEdit = false;

    public function mount(?Account $account = null)
    {
        if ($account && $account->exists) {
            if ($account->user_id != Auth::id() && !Auth::user()->is_admin) {
                abort(403, 'You do not have permission to edit this account.');
            }

            $this->isEdit = true;
            $this->accountId = $account->id;
            $this->name = $account->name;
            $this->balance = $account->balance;
            $this->initial_balance = $account->initial_balance ?? 0;
            $this->account_type = $account->account_type;
            $this->bank_name = $account->bank_name;
            $this->account_holder_name = $account->account_holder_name;
            $this->account_number = $account->account_number;
            $this->ifsc_code = $account->ifsc_code;
            $this->branch_address = $account->branch_address;
        }
    }

    public function save(AccountService $accountService)
    {
        $user = Auth::user();

        if ($this->isEdit) {
            $this->validate([
                'name' => [
                    'required', 'string', 'max:255',
                    Rule::unique('accounts', 'name')
                        ->where(fn ($q) => $q->whereIn('user_id', function ($query) use ($user) {
                            $query->select('id')
                                ->from('users')
                                ->where('unid', $user->unid);
                        }))
                        ->ignore($this->accountId),
                ],
                'balance'      => 'required|numeric',
                'account_type' => 'required|in:general,savings,liability',
                'bank_name'    => 'nullable|string|max:255',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number'      => 'nullable|string|max:255',
                'ifsc_code'           => 'nullable|string|max:255',
                'branch_address'      => 'nullable|string|max:500',
            ]);

            $account = Account::findOrFail($this->accountId);
            $accountService->updateAccount($account, [
                'name'          => $this->name,
                'balance'       => $this->balance,
                'account_type'  => $this->account_type,
                'bank_name'     => $this->bank_name,
                'account_holder_name' => $this->account_holder_name,
                'account_number'      => $this->account_number,
                'ifsc_code'           => $this->ifsc_code,
                'branch_address'      => $this->branch_address,
            ]);

            session()->flash('account-message', 'Account updated successfully.');
        } else {
            $this->validate([
                'name' => [
                    'required', 'string', 'max:255',
                    Rule::unique('accounts', 'name')
                        ->where(fn ($q) => $q->whereIn('user_id', function ($query) use ($user) {
                            $query->select('id')
                                ->from('users')
                                ->where('unid', $user->unid);
                        })),
                ],
                'initial_balance' => 'required|numeric',
                'account_type'    => 'required|in:general,savings,liability',
                'bank_name'       => 'nullable|string|max:255',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number'      => 'nullable|string|max:255',
                'ifsc_code'           => 'nullable|string|max:255',
                'branch_address'      => 'nullable|string|max:500',
            ]);

            $accountService->createAccount([
                'name'          => $this->name,
                'balance'       => $this->initial_balance,
                'initial_balance' => $this->initial_balance,
                'account_type'  => $this->account_type,
                'bank_name'     => $this->bank_name,
                'account_holder_name' => $this->account_holder_name,
                'account_number'      => $this->account_number,
                'ifsc_code'           => $this->ifsc_code,
                'branch_address'      => $this->branch_address,
                'user_id'       => $user->id,
            ]);

            session()->flash('account-message', 'Account created successfully.');
        }

        return redirect('/accounts');
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="/accounts" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $isEdit ? 'Edit Account' : 'New Account' }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $isEdit ? 'Update your account details below.' : 'Fill in the details to create a new account.' }}</p>
        </div>
    </div>

    {{-- Flash message --}}
    @if(session()->has('account-message'))
        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100">
            {{ session('account-message') }}
        </div>
    @endif

    {{-- Form Card --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="bg-indigo-600 px-8 py-5 text-white">
            <h2 class="font-bold text-lg">{{ $isEdit ? 'Account Details' : 'Account Information' }}</h2>
            <p class="text-indigo-200 text-sm mt-0.5">All fields marked with * are required</p>
        </div>

        <form wire:submit="save" class="p-8 space-y-5">

            {{-- Account Name & Type --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Name <span class="text-rose-500">*</span></label>
                    <input type="text" wire:model="name" required placeholder="e.g. HDFC Savings"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                    @error('name') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Type <span class="text-rose-500">*</span></label>
                    <select wire:model="account_type" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm font-semibold">
                        <option value="general">General</option>
                        <option value="savings">Savings</option>
                        <option value="liability">Liability (Owed)</option>
                    </select>
                    @error('account_type') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Balance --}}
            <div class="space-y-1.5">
                @if($isEdit)
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Current Balance <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" wire:model="balance" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                    @error('balance') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                @else
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Initial Balance <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" wire:model="initial_balance" required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                    @error('initial_balance') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                @endif
            </div>

            <div class="border-t border-slate-100 pt-5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Bank Details (Optional)</p>
                <div class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Bank Name</label>
                            <input type="text" wire:model="bank_name" placeholder="e.g. HDFC Bank"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Holder</label>
                            <input type="text" wire:model="account_holder_name" placeholder="Full name"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Number</label>
                            <input type="text" wire:model="account_number"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm font-mono">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">IFSC Code</label>
                            <input type="text" wire:model="ifsc_code"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm font-mono uppercase">
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Branch Address</label>
                        <textarea wire:model="branch_address" rows="2" placeholder="Branch address..."
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm resize-none"></textarea>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 text-sm">
                    {{ $isEdit ? 'Update Account' : 'Create Account' }}
                </button>
                <a href="/accounts"
                    class="px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-all text-sm text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
