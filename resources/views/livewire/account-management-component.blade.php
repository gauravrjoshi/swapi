<?php

use Livewire\Volt\Component;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $name;
    public $initial_balance = 0;
    public $is_savings = false;
    
    public $editingAccountId = null;
    public $editName;
    public $editBalance;
    public $editIsSavings = false;

    public $confirmingDeletionId = null;

    public function createAccount()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'initial_balance' => 'required|numeric',
            'is_savings' => 'boolean',
        ]);

        Account::create([
            'name' => $this->name,
            'balance' => $this->initial_balance,
            'is_savings' => $this->is_savings,
            'user_id' => Auth::id(),
        ]);

        $this->reset(['name', 'initial_balance', 'is_savings']);
        session()->flash('account-message', 'Account created successfully.');
    }

    public function editAccount($id)
    {
        $account = Account::where('user_id', Auth::id())->findOrFail($id);

        $this->editingAccountId = $id;
        $this->editName = $account->name;
        $this->editBalance = $account->balance;
        $this->editIsSavings = (bool) $account->is_savings;
    }

    public function updateAccount()
    {
        $account = Account::where('user_id', Auth::id())->findOrFail($this->editingAccountId);

        $validated = $this->validate([
            'editName' => 'required|string|max:255',
            'editBalance' => 'required|numeric',
            'editIsSavings' => 'boolean',
        ]);

        $account->update([
            'name' => $this->editName,
            'balance' => $this->editBalance,
            'is_savings' => $this->editIsSavings,
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
            $account = Account::where('user_id', Auth::id())->findOrFail($this->confirmingDeletionId);

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
        $this->reset(['editingAccountId', 'editName', 'editBalance', 'editIsSavings']);
    }

    public function with()
    {
        return [
            'accounts' => Account::where('user_id', Auth::id())->get(),
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
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Name</label>
                                <input type="text" wire:model="editName" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Current Balance</label>
                                <input type="number" step="0.01" wire:model="editBalance" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                            <div class="flex items-center gap-3 p-1">
                                <input type="checkbox" wire:model="editIsSavings" id="editIsSavings" class="w-5 h-5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                <label for="editIsSavings" class="text-sm font-bold text-slate-700">Is Savings Account?</label>
                            </div>
                            <div class="flex gap-2">
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
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Account Name</label>
                                <input type="text" wire:model="name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="e.g. HDFC Bank">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase ml-1">Initial Balance</label>
                                <input type="number" step="0.01" wire:model="initial_balance" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all">
                            </div>
                            <div class="flex items-center gap-3 p-1">
                                <input type="checkbox" wire:model="is_savings" id="is_savings" class="w-5 h-5 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                <label for="is_savings" class="text-sm font-bold text-slate-700">Is Savings Account?</label>
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
                                        <p class="text-sm font-black text-indigo-600">₹{{ number_format($account->balance, 2) }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
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
</div>