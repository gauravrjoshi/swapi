<?php

use Livewire\Volt\Component;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

new class extends Component
{
    // Modal state
    public bool $showModal = false;
    public bool $isEdit = false;

    // Form fields
    public $formAccountId = null;
    public $name;
    public $initial_balance = 0;
    public $balance = 0;
    public $account_type = 'savings';
    public $bank_name;
    public $account_holder_name;
    public $account_number;
    public $ifsc_code;
    public $branch_address;

    // Other modals
    public $sharingAccountId = null;
    public $confirmingDeletionId = null;

    public function openCreate()
    {
        $this->reset(['formAccountId','name','initial_balance','balance','account_type','bank_name','account_holder_name','account_number','ifsc_code','branch_address']);
        $this->account_type = 'savings';
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function openEdit($id)
    {
        $account = Account::findOrFail($id);
        if ($account->user_id != Auth::id() && !Auth::user()->is_admin) {
            session()->flash('account-error', 'No permission to edit this account.');
            return;
        }
        $this->formAccountId = $account->id;
        $this->name = $account->name;
        $this->balance = $account->balance;
        $this->initial_balance = $account->initial_balance ?? 0;
        $this->account_type = $account->account_type;
        $this->bank_name = $account->bank_name;
        $this->account_holder_name = $account->account_holder_name;
        $this->account_number = $account->account_number;
        $this->ifsc_code = $account->ifsc_code;
        $this->branch_address = $account->branch_address;
        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save(AccountService $accountService)
    {
        if ($this->isEdit) {
            $this->validate([
                'name' => ['required','string','max:255', Rule::unique('accounts','name')->where(fn($q)=>$q->where('user_id',Auth::id()))->ignore($this->formAccountId)],
                'balance' => 'required|numeric',
                'account_type' => 'required|in:general,savings,liability',
                'bank_name' => 'nullable|string|max:255',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'ifsc_code' => 'nullable|string|max:255',
                'branch_address' => 'nullable|string|max:500',
            ]);
            $account = Account::findOrFail($this->formAccountId);
            $accountService->updateAccount($account, [
                'name' => $this->name,
                'balance' => $this->balance,
                'account_type' => $this->account_type,
                'bank_name' => $this->bank_name,
                'account_holder_name' => $this->account_holder_name,
                'account_number' => $this->account_number,
                'ifsc_code' => $this->ifsc_code,
                'branch_address' => $this->branch_address,
            ]);
            session()->flash('account-message', 'Account updated successfully.');
        } else {
            $this->validate([
                'name' => ['required','string','max:255', Rule::unique('accounts','name')->where(fn($q)=>$q->where('user_id',Auth::id()))],
                'initial_balance' => 'required|numeric',
                'account_type' => 'required|in:general,savings,liability',
                'bank_name' => 'nullable|string|max:255',
                'account_holder_name' => 'nullable|string|max:255',
                'account_number' => 'nullable|string|max:255',
                'ifsc_code' => 'nullable|string|max:255',
                'branch_address' => 'nullable|string|max:500',
            ]);
            $accountService->createAccount([
                'name' => $this->name,
                'initial_balance' => $this->initial_balance,
                'account_type' => $this->account_type,
                'bank_name' => $this->bank_name,
                'account_holder_name' => $this->account_holder_name,
                'account_number' => $this->account_number,
                'ifsc_code' => $this->ifsc_code,
                'branch_address' => $this->branch_address,
                'user_id' => Auth::id(),
            ]);
            session()->flash('account-message', 'Account created successfully.');
        }
        $this->showModal = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function confirmDelete($id) { $this->confirmingDeletionId = $id; }

    public function deleteAccount(AccountService $accountService)
    {
        try {
            $account = Account::findOrFail($this->confirmingDeletionId);
            $accountService->deleteAccount($account);
            $this->confirmingDeletionId = null;
            session()->flash('account-message', 'Account deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('account-error', 'Could not delete: ' . $e->getMessage());
        }
    }

    public function cancelDelete() { $this->confirmingDeletionId = null; }
    public function shareAccount($id) { $this->sharingAccountId = $id; }
    public function closeShare() { $this->sharingAccountId = null; }

    public function with(AccountService $accountService)
    {
        $accounts = $accountService->getAccounts(Auth::id());
        return ['accounts' => $accounts];
    }
};
?>

<div x-data="{ showBalances: true }">
    <div class="p-6 w-full mx-auto space-y-8 relative z-10">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="bg-indigo-600 p-8 text-white flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">Manage Accounts</h2>
                    <p class="text-indigo-100 mt-1">View, share, or delete your bank accounts</p>
                </div>
                <button wire:click="openCreate"
                    class="bg-white text-indigo-600 hover:bg-indigo-50 font-bold px-5 py-2.5 rounded-xl transition-all flex items-center gap-2 shadow-md text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    New Account
                </button>
            </div>

            <div class="p-8">

                @if(session()->has('account-message'))
                    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100 mb-6">
                        {{ session('account-message') }}
                    </div>
                @endif

                @if(session()->has('account-error'))
                    <div class="bg-rose-50 text-rose-700 p-4 rounded-2xl text-sm border border-rose-100 mb-6">
                        {{ session('account-error') }}
                    </div>
                @endif

                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-bold text-slate-800">Existing Accounts</h3>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-lg cursor-pointer hover:bg-slate-200 transition-all" @click="showBalances = !showBalances">
                        <template x-if="showBalances">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </template>
                        <template x-if="!showBalances">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </template>
                        <span class="text-[10px] font-bold text-slate-600 uppercase" x-text="showBalances ? 'Hide' : 'Show'"></span>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-white border border-slate-100 rounded-2xl shadow-sm">
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-md z-10">
                                <tr class="border-b border-slate-100">
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Account</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Balance</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ownership</th>
                                    <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($accounts as $account)
                                    <tr class="group hover:bg-slate-50/50 transition-all">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-all">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <p class="font-bold text-slate-900 line-clamp-1">{{ $account->name }}</p>
                                                        @if($account->account_type === 'savings' || $account->is_savings)
                                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-100">Savings</span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">Initial: ₹{{ number_format($account->initial_balance ?? 0, 2) }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-black text-indigo-600 whitespace-nowrap" x-text="showBalances ? '₹{{ number_format($account->balance, 2) }}' : '₹ ••••'"></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[9px] font-bold uppercase rounded-lg border border-slate-200">{{ $account->user ? strtok($account->user->name, ' ') : 'System' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-1">
                                                <button wire:click="shareAccount({{ $account->id }})" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Share">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="openEdit({{ $account->id }})" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="confirmDelete({{ $account->id }})" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 000-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-300">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                                <p class="text-slate-500 font-medium">No accounts found.</p>
                                                <a href="/accounts/new" class="text-indigo-600 font-bold text-sm hover:underline">Create your first account</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if($showModal)
        @teleport('body')
        <div class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="background-color:rgba(0,0,0,0.6);backdrop-filter:blur(4px)">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden" style="max-height:90vh;overflow-y:auto">
                {{-- Modal Header --}}
                <div class="bg-indigo-600 px-8 py-5 text-white flex justify-between items-center sticky top-0 z-10">
                    <div>
                        <h2 class="text-xl font-bold">{{ $isEdit ? 'Edit Account' : 'New Account' }}</h2>
                        <p class="text-indigo-200 text-sm mt-0.5">{{ $isEdit ? 'Update your account details.' : 'Fill in the details to create a new account.' }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-indigo-200 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Form --}}
                <form wire:submit="save" class="p-8 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="space-y-1.5 md:col-span-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="name" required placeholder="e.g. HDFC Savings"
                                class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                            @error('name') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Type <span class="text-rose-500">*</span></label>
                            <select wire:model="account_type" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm font-semibold">
                                <option value="general">General</option>
                                <option value="savings">Savings</option>
                                <option value="liability">Liability (Owed)</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        @if($isEdit)
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Current Balance <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" wire:model="balance" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                            @error('balance') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                        @else
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Initial Balance <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" wire:model="initial_balance" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                            @error('initial_balance') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    <div class="border-t border-slate-100 pt-5">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Bank Details (Optional)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Bank Name</label>
                                <input type="text" wire:model="bank_name" placeholder="e.g. HDFC Bank" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Holder</label>
                                <input type="text" wire:model="account_holder_name" placeholder="Full name" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Account Number</label>
                                <input type="text" wire:model="account_number" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm font-mono">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">IFSC Code</label>
                                <input type="text" wire:model="ifsc_code" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm font-mono uppercase">
                            </div>
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Branch Address</label>
                                <textarea wire:model="branch_address" rows="2" placeholder="Branch address..." class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all text-sm resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="flex-1 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-indigo-100 text-sm">
                            {{ $isEdit ? 'Update Account' : 'Create Account' }}
                        </button>
                        <button type="button" wire:click="closeModal" class="px-6 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-all text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endteleport
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($confirmingDeletionId)
        @teleport('body')
            <div class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
                <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 space-y-6">
                    <div class="h-20 w-20 flex items-center justify-center mx-auto rounded-full bg-rose-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <h3 class="text-2xl font-black text-slate-900">Are you sure?</h3>
                        <p class="text-slate-500 mt-2 leading-relaxed">This action is permanent. All history for this account will be removed.</p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button wire:click="cancelDelete" class="flex-1 py-3.5 bg-slate-100 text-slate-700 rounded-2xl font-bold hover:bg-slate-200 transition-all">Cancel</button>
                        <button wire:click="deleteAccount" class="flex-1 py-3.5 bg-rose-500 text-white rounded-2xl font-bold hover:bg-rose-600 transition-all shadow-lg">Delete</button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    {{-- Share Modal --}}
    @if($sharingAccountId)
        @php $shareAccount = \App\Models\Account::find($sharingAccountId); @endphp
        @teleport('body')
            <div x-data="{ copying: false }" class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <div class="bg-indigo-600 p-6 text-white flex justify-between items-center">
                        <h3 class="text-xl font-bold">Share Account Details</h3>
                        <button wire:click="closeShare" class="text-indigo-200 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <div id="share-card" class="bg-gradient-to-br from-indigo-500 to-purple-600 px-6 pt-6 rounded-2xl text-white shadow-lg space-y-4 relative overflow-hidden" style="padding-bottom: 40px; min-height: 100%;">
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                            <div class="flex justify-between items-start relative z-10">
                                <div>
                                    <p class="text-indigo-100 text-xs font-bold uppercase tracking-widest">Bank Name</p>
                                    <p class="text-xl font-black">{{ $shareAccount->bank_name ?? $shareAccount->name }}</p>
                                </div>
                                <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="space-y-3 pt-2 relative z-10">
                                <div>
                                    <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest">Account Holder</p>
                                    <p class="font-bold">{{ $shareAccount->account_holder_name ?? 'N/A' }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest">Account Number</p>
                                        <p class="font-mono font-bold tracking-wider">{{ $shareAccount->account_number ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest">IFSC Code</p>
                                        <p class="font-mono font-bold tracking-wider">{{ $shareAccount->ifsc_code ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest">Branch</p>
                                    <p class="text-sm font-medium whitespace-nowrap overflow-hidden text-ellipsis block">{{ $shareAccount->branch_address ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button
                                @click="
                                    const text = `Bank: {{ $shareAccount->bank_name }}\nHolder: {{ $shareAccount->account_holder_name }}\nA/C: {{ $shareAccount->account_number }}\nIFSC: {{ $shareAccount->ifsc_code }}`;
                                    navigator.clipboard.writeText(text);
                                    copying = true;
                                    setTimeout(() => copying = false, 2000);
                                "
                                class="w-full py-4 rounded-2xl font-bold flex items-center justify-center gap-3 transition-all border-2 border-dashed border-indigo-200 text-indigo-600 hover:bg-indigo-50"
                            >
                                <svg x-show="!copying" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                                <svg x-show="copying" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="copying ? 'Copied to Clipboard!' : 'Copy Text Details'"></span>
                            </button>

                            <button onclick="downloadCard()"
                                class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold flex items-center justify-center gap-3 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Save as Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <script>
        function downloadCard() {
            const card = document.getElementById('share-card');
            const bounds = card.getBoundingClientRect();
            html2canvas(card, { 
                scale: 2, 
                backgroundColor: null, 
                useCORS: true,
                width: bounds.width,
                height: bounds.height,
                windowWidth: bounds.width,
                windowHeight: bounds.height
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'account-details.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</div>