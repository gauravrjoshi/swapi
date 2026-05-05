<?php

use Livewire\Volt\Component;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $name;
    public $initial_balance = 0;
    public $account_type = 'savings';
    public $bank_name;
    public $account_holder_name;
    public $account_number;
    public $ifsc_code;
    public $branch_address;
    
    public $editingAccountId = null;
    public $editName;
    public $editBalance;
    public $editAccountType = 'savings';
    public $editBankName;
    public $editAccountHolderName;
    public $editAccountNumber;
    public $editIfscCode;
    public $editBranchAddress;

    public $sharingAccountId = null;
    public $confirmingDeletionId = null;

    public function createAccount()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'initial_balance' => 'required|numeric',
            'account_type' => 'required|in:general,savings,liability',
            'bank_name' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:255',
            'branch_address' => 'nullable|string|max:500',
        ]);

        Account::create([
            'name' => $this->name,
            'balance' => $this->initial_balance,
            'initial_balance' => $this->initial_balance,
            'account_type' => $this->account_type,
            'bank_name' => $this->bank_name,
            'account_holder_name' => $this->account_holder_name,
            'account_number' => $this->account_number,
            'ifsc_code' => $this->ifsc_code,
            'branch_address' => $this->branch_address,
            'user_id' => Auth::id(),
        ]);

        $this->reset(['name', 'initial_balance', 'account_type', 'bank_name', 'account_holder_name', 'account_number', 'ifsc_code', 'branch_address']);
        session()->flash('account-message', 'Account created successfully.');
    }

    public function editAccount($id)
    {
        $account = Account::findOrFail($id);

        $this->editingAccountId = $id;
        $this->editName = $account->name;
        $this->editBalance = $account->balance;
        $this->editAccountType = $account->account_type;
        $this->editBankName = $account->bank_name;
        $this->editAccountHolderName = $account->account_holder_name;
        $this->editAccountNumber = $account->account_number;
        $this->editIfscCode = $account->ifsc_code;
        $this->editBranchAddress = $account->branch_address;
    }

    public function updateAccount()
    {
        $account = Account::findOrFail($this->editingAccountId);

        $validated = $this->validate([
            'editName' => 'required|string|max:255',
            'editBalance' => 'required|numeric',
            'editAccountType' => 'required|in:general,savings,liability',
            'editBankName' => 'nullable|string|max:255',
            'editAccountHolderName' => 'nullable|string|max:255',
            'editAccountNumber' => 'nullable|string|max:255',
            'editIfscCode' => 'nullable|string|max:255',
            'editBranchAddress' => 'nullable|string|max:500',
        ]);

        $account->update([
            'name' => $this->editName,
            'balance' => $this->editBalance,
            'account_type' => $this->editAccountType,
            'bank_name' => $this->editBankName,
            'account_holder_name' => $this->editAccountHolderName,
            'account_number' => $this->editAccountNumber,
            'ifsc_code' => $this->editIfscCode,
            'branch_address' => $this->editBranchAddress,
        ]);

        $this->cancelEdit();
        session()->flash('account-message', 'Account updated successfully.');
    }

    public function confirmDelete($id)
    {
        $this->confirmingDeletionId = $id;
    }

    public function deleteAccount()
    {
        try {
            $account = Account::findOrFail($this->confirmingDeletionId);

            $account->delete();
            $this->confirmingDeletionId = null;
            session()->flash('account-message', 'Account deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('account-error', 'Could not delete account: ' . $e->getMessage());
        }
    }

    public function cancelDelete()
    {
        $this->confirmingDeletionId = null;
    }

    public function cancelEdit()
    {
        $this->reset(['editingAccountId', 'editName', 'editBalance', 'editIsSavings', 'editBankName', 'editAccountHolderName', 'editAccountNumber', 'editIfscCode', 'editBranchAddress']);
    }

    public function shareAccount($id)
    {
        $this->sharingAccountId = $id;
    }

    public function closeShare()
    {
        $this->sharingAccountId = null;
    }

    public function with()
    {
        return [
            'accounts' => Account::with('user')->get(),
        ];
    }
};
?>

<div>
    <div class="p-6 w-full mx-auto space-y-8 relative z-10">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
            <div class="bg-indigo-600 p-8 text-white flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold">Manage Accounts</h2>
                    <p class="text-indigo-100 mt-1">Add or edit your bank accounts and balances</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-slate-100">
                <!-- Form Area -->
                <div class="p-8 space-y-6 bg-slate-50">
                    @if($editingAccountId)
                        <h3 class="text-lg font-bold text-slate-800">Edit Account</h3>
                        <form wire:submit="updateAccount" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="editName" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                @error('editName') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">Current Balance <span class="text-rose-500">*</span></label>
                                    <input type="number" step="0.01" wire:model="editBalance" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                    @error('editBalance') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Type <span class="text-rose-500">*</span></label>
                                    <select wire:model="editAccountType" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-black text-sm">
                                        <option value="general">General </option>
                                        <option value="savings">Savings</option>
                                        <option value="liability">Liability (Owed)</option>
                                    </select>
                                    @error('editAccountType') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Bank Name</label>
                                <input type="text" wire:model="editBankName" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Holder</label>
                                <input type="text" wire:model="editAccountHolderName" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Number</label>
                                    <input type="text" wire:model="editAccountNumber" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">IFSC Code</label>
                                    <input type="text" wire:model="editIfscCode" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Branch Address</label>
                                <textarea wire:model="editBranchAddress" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all"></textarea>
                            </div>
                            <div class="flex gap-2 pt-2">
                                <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                                    Update
                                </button>
                                <button type="button" wire:click="cancelEdit" class="px-4 py-3 bg-white border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-100 transition-all">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @else
                        <h3 class="text-lg font-bold text-slate-800">Add Account</h3>
                        <form wire:submit="createAccount" class="space-y-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="e.g. HDFC Bank">
                                @error('name') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">Initial Balance <span class="text-rose-500">*</span></label>
                                    <input type="number" step="0.01" wire:model="initial_balance" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                    @error('initial_balance') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Type <span class="text-rose-500">*</span></label>
                                    <select wire:model="account_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-black text-sm">
                                        <option value="general">General  </option>
                                        <option value="savings">Savings</option>
                                        <option value="liability">Liability (Owed)</option>
                                    </select>
                                    @error('account_type') <span class="text-[10px] text-rose-500 font-bold ml-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Bank Name</label>
                                <input type="text" wire:model="bank_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="e.g. HDFC Bank">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Holder</label>
                                <input type="text" wire:model="account_holder_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="John Doe">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Number</label>
                                    <input type="text" wire:model="account_number" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-bold text-slate-500 uppercase ml-1">IFSC Code</label>
                                    <input type="text" wire:model="ifsc_code" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Branch Address</label>
                                <textarea wire:model="branch_address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all"></textarea>
                            </div>
                            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                                Create Account
                            </button>
                        </form>
                    @endif

                    @if (session()->has('account-message'))
                        <div class="bg-emerald-50 text-emerald-700 p-4 rounded-2xl text-sm border border-emerald-100 animate-pulse">
                            {{ session('account-message') }}
                        </div>
                    @endif

                    @if (session()->has('account-error'))
                        <div class="bg-rose-50 text-rose-700 p-4 rounded-2xl text-sm border border-rose-100">
                            {{ session('account-error') }}
                        </div>
                    @endif
                </div>

                <!-- List Area -->
                <div class="p-8 md:col-span-2">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Existing Accounts</h3>
                    <div class="space-y-3">
                        @foreach($accounts as $account)
                            <div class="group flex items-center justify-between p-5 bg-white border border-slate-100 rounded-2xl hover:border-indigo-200 transition-all shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-bold text-slate-900">{{ $account->name }}</p>
                                            @if($account->is_savings)
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-100">Savings</span>
                                            @endif
                                        </div>
                                        <p class="text-sm font-black text-indigo-600" x-text="showBalances ? '₹{{ number_format($account->balance, 2) }}' : '₹ ••••'"></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">
                                            Initial: ₹{{ number_format($account->initial_balance ?? 0, 2) }} • By {{ $account->user?->name ?? 'System' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="shareAccount({{ $account->id }})" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-all" title="Share Account Details">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z" />
                                        </svg>
                                    </button>
                                    <button wire:click="editAccount({{ $account->id }})" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $account->id }})" 
                                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 000-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teleported Modal to Body -->
    @if($confirmingDeletionId)
        @teleport('body')
            <div class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-center; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
                <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 space-y-6" style="background-color: white; border-radius: 1.5rem; width: 100%; max-width: 24rem; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                    <div class="h-20 w-20 flex items-center justify-center mx-auto rounded-full" style="background-color: #fff1f2; height: 5rem; width: 5rem; margin-left: auto; margin-right: auto; display: flex; align-items: center; justify-center; border-radius: 9999px;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" style="color: #e11d48; height: 2.5rem; width: 2.5rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="text-center" style="text-align: center;">
                        <h3 class="text-2xl font-black text-slate-900" style="font-size: 1.5rem; font-weight: 900; color: #0f172a;">Are you sure?</h3>
                        <p class="text-slate-500 mt-2 leading-relaxed" style="color: #64748b; margin-top: 0.5rem; line-height: 1.625;">This action is permanent. All history for this account will be removed.</p>
                    </div>
                    <div class="flex gap-3 pt-2" style="display: flex; gap: 0.75rem; padding-top: 0.5rem;">
                        <button wire:click="cancelDelete" class="flex-1 py-3.5 bg-slate-100 text-slate-700 rounded-2xl font-bold hover:bg-slate-200 transition-all" style="flex: 1; padding-top: 0.875rem; padding-bottom: 0.875rem; background-color: #f1f5f9; color: #334155; border-radius: 1rem; font-weight: 700; border: none; cursor: pointer;">
                            Cancel
                        </button>
                        <button wire:click="deleteAccount" class="flex-1 py-3.5 text-white rounded-2xl font-bold hover:opacity-90 transition-all shadow-lg" style="flex: 1; padding-top: 0.875rem; padding-bottom: 0.875rem; background-color: #e11d48; color: white; border-radius: 1rem; font-weight: 700; border: none; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(225, 29, 72, 0.3);">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif

    <!-- Share Modal -->
    @if($sharingAccountId)
        @php $shareAccount = \App\Models\Account::find($sharingAccountId); @endphp
        @teleport('body')
            <div x-data="{ copying: false }" class="fixed inset-0 z-[99999] flex items-center justify-center p-4" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-center; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px);">
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden" style="background-color: white; border-radius: 1.5rem; width: 100%; max-width: 32rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
                    <div class="bg-indigo-600 p-6 text-white flex justify-between items-center">
                        <h3 class="text-xl font-bold">Share Account Details</h3>
                        <button wire:click="closeShare" class="text-indigo-200 hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <!-- Preview Card (This will be captured as image) -->
                        <div id="share-card" class="bg-gradient-to-br from-indigo-500 to-purple-600 p-6 rounded-2xl text-white shadow-lg space-y-4 relative overflow-hidden">
                            <!-- Decorative circles -->
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                            
                            <div class="flex justify-between items-start">
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

                            <div class="space-y-3 pt-2">
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
                                    <p class="text-sm font-medium line-clamp-1">{{ $shareAccount->branch_address ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
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

                            <button 
                                onclick="downloadCard()"
                                class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold flex items-center justify-center gap-3 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100"
                            >
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
            html2canvas(card, {
                scale: 2,
                backgroundColor: null,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'account-details.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</div>