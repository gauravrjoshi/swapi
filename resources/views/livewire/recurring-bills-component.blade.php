<?php

use Livewire\Volt\Component;
use App\Models\RecurringBill;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $name = '';
    public $amount = '';
    public $type = 'expense'; // income, expense
    public $frequency = 'monthly'; // daily, weekly, monthly, yearly
    public $next_due_date = '';
    public $is_active = true;
    public $search = '';

    public $editingBillId = null;
    public $editName = '';
    public $editAmount = '';
    public $editType = 'expense';
    public $editFrequency = 'monthly';
    public $editNextDueDate = '';
    public $editIsActive = true;

    public function createBill()
    {
        $userId = Auth::id();

        $this->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|string|in:income,expense',
            'frequency' => 'required|string|in:daily,weekly,monthly,yearly',
            'next_due_date' => 'required|date|after_or_equal:today',
        ]);

        RecurringBill::create([
            'user_id' => $userId,
            'name' => $this->name,
            'amount' => $this->amount,
            'type' => $this->type,
            'frequency' => $this->frequency,
            'next_due_date' => $this->next_due_date,
            'is_active' => $this->is_active,
        ]);

        session()->flash('bill-message', 'Recurring bill/credit created successfully.');
        $this->reset(['name', 'amount', 'type', 'frequency', 'next_due_date', 'is_active']);
    }

    public function editBill($id)
    {
        $bill = RecurringBill::where('user_id', Auth::id())->findOrFail($id);
        $this->editingBillId = $id;
        $this->editName = $bill->name;
        $this->editAmount = $bill->amount;
        $this->editType = $bill->type;
        $this->editFrequency = $bill->frequency;
        $this->editNextDueDate = $bill->next_due_date->format('Y-m-d');
        $this->editIsActive = $bill->is_active;
    }

    public function updateBill()
    {
        $userId = Auth::id();

        $this->validate([
            'editName' => 'required|string|max:255',
            'editAmount' => 'required|numeric|min:0.01',
            'editType' => 'required|string|in:income,expense',
            'editFrequency' => 'required|string|in:daily,weekly,monthly,yearly',
            'editNextDueDate' => 'required|date',
        ]);

        $bill = RecurringBill::where('user_id', $userId)->findOrFail($this->editingBillId);
        $bill->update([
            'name' => $this->editName,
            'amount' => $this->editAmount,
            'type' => $this->editType,
            'frequency' => $this->editFrequency,
            'next_due_date' => $this->editNextDueDate,
            'is_active' => $this->editIsActive,
        ]);

        $this->cancelEdit();
        session()->flash('bill-message', 'Recurring bill/credit updated successfully.');
    }

    public function deleteBill($id)
    {
        RecurringBill::where('user_id', Auth::id())->findOrFail($id)->delete();
        session()->flash('bill-message', 'Recurring bill/credit deleted successfully.');
    }

    public function toggleActive($id)
    {
        $bill = RecurringBill::where('user_id', Auth::id())->findOrFail($id);
        $bill->update([
            'is_active' => !$bill->is_active
        ]);
    }

    public function cancelEdit()
    {
        $this->reset(['editingBillId', 'editName', 'editAmount', 'editType', 'editFrequency', 'editNextDueDate', 'editIsActive']);
    }

    public function with()
    {
        $userId = Auth::id();
        $bills = RecurringBill::where('user_id', $userId)
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . trim($this->search) . '%');
            })
            ->get();

        $totalMonthlyIncome = 0.0;
        $totalMonthlyExpense = 0.0;
        $nextDueBill = null;
        $closestDiffDays = null;

        foreach ($bills as $b) {
            $dueDate = Carbon::parse($b->next_due_date)->startOfDay();
            $today = Carbon::today();
            $b->days_left = $today->diffInDays($dueDate, false);

            if ($b->is_active) {
                // Monthly Equivalent Cost
                $monthlyEquivalent = 0.0;
                switch ($b->frequency) {
                    case 'daily':
                        $monthlyEquivalent = ($b->amount * 30);
                        break;
                    case 'weekly':
                        $monthlyEquivalent = ($b->amount * 52 / 12);
                        break;
                    case 'monthly':
                        $monthlyEquivalent = $b->amount;
                        break;
                    case 'yearly':
                        $monthlyEquivalent = ($b->amount / 12);
                        break;
                }

                if ($b->type === 'income') {
                    $totalMonthlyIncome += $monthlyEquivalent;
                } else {
                    $totalMonthlyExpense += $monthlyEquivalent;
                }

                // Next Due Bill computation (only active items where due date is today or in future)
                if ($b->days_left >= 0) {
                    if ($nextDueBill === null || $b->days_left < $closestDiffDays) {
                        $nextDueBill = $b;
                        $closestDiffDays = $b->days_left;
                    }
                }
            }
        }

        return [
            'bills' => $bills,
            'totalMonthlyIncome' => (float)$totalMonthlyIncome,
            'totalMonthlyExpense' => (float)$totalMonthlyExpense,
            'nextDueBill' => $nextDueBill,
        ];
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen">
    <!-- Header Section -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Recurring Bills & Credits</h1>
            <p class="text-slate-500 mt-1">Track and manage your scheduled income and utility bills</p>
        </div>
    </header>

    <!-- Metrics Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-emerald-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Est. Monthly Income</p>
            <p class="text-2xl font-bold text-emerald-600 mt-2" x-text="showBalances ? '₹{{ number_format($totalMonthlyIncome, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-rose-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Est. Monthly Bills</p>
            <p class="text-2xl font-bold text-rose-600 mt-2" x-text="showBalances ? '₹{{ number_format($totalMonthlyExpense, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-amber-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Next Upcoming Bill/Credit</p>
            @if($nextDueBill)
                <div class="mt-2 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-base font-bold text-slate-800 truncate">{{ $nextDueBill->name }}</p>
                        <p class="text-xs font-semibold text-slate-500 mt-0.5">
                            @if($nextDueBill->days_left == 0)
                                Due today!
                            @elseif($nextDueBill->days_left == 1)
                                Due tomorrow
                            @else
                                Due in {{ $nextDueBill->days_left }} days ({{ $nextDueBill->next_due_date->format('M d, Y') }})
                            @endif
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-xl text-xs font-black shrink-0 {{ $nextDueBill->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                        {{ $nextDueBill->type }}
                    </span>
                </div>
            @else
                <p class="text-sm font-semibold text-slate-400 mt-2.5">No upcoming bills found</p>
            @endif
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Form Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden lg:col-span-1">
            <div class="p-6 text-white bg-gradient-to-br from-indigo-600 to-indigo-700">
                <h3 class="text-lg font-bold">{{ $editingBillId ? 'Edit Recurring Item' : 'Add Recurring Item' }}</h3>
                <p class="text-xs text-indigo-200 mt-1">Schedule recurring expenses and periodic credits</p>
            </div>
            
            <div class="p-6">
                @if(session()->has('bill-message'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-600 rounded-xl text-sm font-semibold border border-emerald-100">
                        {{ session('bill-message') }}
                    </div>
                @endif

                @if($editingBillId)
                    <form wire:submit="updateBill" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Item Name</label>
                            <input type="text" wire:model="editName"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. Rent, Salary, Electricity">
                            @error('editName') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="editAmount"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. 15000">
                            @error('editAmount') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Transaction Type</label>
                            <select wire:model="editType" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                                <option value="expense">Expense (Debit / Bill)</option>
                                <option value="income">Income (Credit / Salary)</option>
                            </select>
                            @error('editType') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Frequency</label>
                            <select wire:model="editFrequency" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            @error('editFrequency') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Next Due Date</label>
                            <input type="date" wire:model="editNextDueDate"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                            @error('editNextDueDate') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2 py-2">
                            <input type="checkbox" wire:model="editIsActive" id="editIsActive" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <label for="editIsActive" class="text-sm font-bold text-slate-600 select-none">Active (Send reminders)</label>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit"
                                class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100">
                                Update
                            </button>
                            <button type="button" wire:click="cancelEdit"
                                class="px-4 py-3 bg-slate-100 border border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                @else
                    <form wire:submit="createBill" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Item Name</label>
                            <input type="text" wire:model="name"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. Broadband, Rent, Salary">
                            @error('name') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="amount"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. 1500">
                            @error('amount') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Transaction Type</label>
                            <select wire:model="type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                                <option value="expense">Expense (Debit / Bill)</option>
                                <option value="income">Income (Credit / Salary)</option>
                            </select>
                            @error('type') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Frequency</label>
                            <select wire:model="frequency" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            @error('frequency') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Next Due Date</label>
                            <input type="date" wire:model="next_due_date"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                            @error('next_due_date') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2 py-2">
                            <input type="checkbox" wire:model="is_active" id="isActive" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <label for="isActive" class="text-sm font-bold text-slate-600 select-none">Active (Send reminders)</label>
                        </div>

                        <button type="submit"
                            class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-2">
                            Add Recurring Item
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Subscription Grid Section -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Search bar -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                <span class="text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live="search"
                    placeholder="Search bills by name..."
                    class="w-full border-none focus:ring-0 text-sm font-medium text-slate-800 p-0">
            </div>

            <!-- Subscriptions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($bills as $b)
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col justify-between space-y-4 relative group hover:shadow-md hover:border-slate-200 transition-all border-l-4 {{ $b->type === 'income' ? 'border-l-emerald-500' : 'border-l-rose-500' }}">
                        <!-- Top Details -->
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-base font-bold text-slate-800 tracking-tight">{{ $b->name }}</h4>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-500">
                                        {{ $b->frequency }}
                                    </span>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $b->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                        {{ $b->type }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="editBill({{ $b->id }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-all" title="Edit Item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button wire:click="deleteBill({{ $b->id }})" onclick="confirm('Are you sure you want to delete this recurring item?') || event.stopImmediatePropagation()" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-50 rounded-lg transition-all" title="Delete Item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Mid Content: Cost & Renewal Status -->
                        <div class="flex justify-between items-baseline pt-2">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Rate</p>
                                <p class="text-xl font-black text-slate-800 mt-0.5" x-text="showBalances ? '₹{{ number_format($b->amount, 2) }}' : '₹ ••••'"></p>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Next Due</p>
                                <p class="text-xs font-bold mt-0.5 text-slate-700">{{ $b->next_due_date->format('M d, Y') }}</p>
                            </div>
                        </div>

                        <!-- Bottom: Active Toggle and Days Remaining -->
                        <div class="flex justify-between items-center pt-3 border-t border-slate-50 text-[10px] font-bold uppercase tracking-wider">
                            <button wire:click="toggleActive({{ $b->id }})" class="flex items-center gap-1.5 transition-colors focus:outline-none">
                                <span class="h-2 w-2 rounded-full {{ $b->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                <span class="{{ $b->is_active ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $b->is_active ? 'Active' : 'Paused' }}
                                </span>
                            </button>

                            @if($b->is_active)
                                @if($b->days_left == 0)
                                    <span class="text-rose-600">Due today!</span>
                                @elseif($b->days_left == 1)
                                    <span class="text-amber-600">Due tomorrow</span>
                                @elseif($b->days_left > 1)
                                    <span class="text-slate-500">{{ $b->days_left }} Days Left</span>
                                @else
                                    <span class="text-rose-500">Overdue ({{ abs($b->days_left) }}d)</span>
                                @endif
                            @else
                                <span class="text-slate-400">Suspended</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-12 rounded-3xl border border-slate-100 text-center col-span-1 md:col-span-2 flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h4 class="text-lg font-bold text-slate-700">No active recurring bills/credits found</h4>
                        <p class="text-sm text-slate-400 mt-1 max-w-xs">Use the config form on the left to schedule your first recurring salary or periodic utility bill.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
