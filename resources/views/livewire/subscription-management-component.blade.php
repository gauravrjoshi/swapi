<?php

use Livewire\Volt\Component;
use App\Models\PersonalSubscription;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component {
    public $name = '';
    public $amount = '';
    public $billing_cycle = 'monthly';
    public $next_renewal_date = '';
    public $is_active = true;
    public $search = '';

    public $editingSubscriptionId = null;
    public $editName = '';
    public $editAmount = '';
    public $editBillingCycle = 'monthly';
    public $editNextRenewalDate = '';
    public $editIsActive = true;

    public function createSubscription()
    {
        $userId = Auth::id();

        $this->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'billing_cycle' => 'required|string|in:daily,weekly,monthly,yearly',
            'next_renewal_date' => 'required|date|after_or_equal:today',
        ]);

        PersonalSubscription::create([
            'user_id' => $userId,
            'name' => $this->name,
            'amount' => $this->amount,
            'billing_cycle' => $this->billing_cycle,
            'next_renewal_date' => $this->next_renewal_date,
            'is_active' => $this->is_active,
        ]);

        session()->flash('subscription-message', 'Subscription created successfully.');
        $this->reset(['name', 'amount', 'billing_cycle', 'next_renewal_date', 'is_active']);
    }

    public function editSubscription($id)
    {
        $sub = PersonalSubscription::findOrFail($id);
        $this->editingSubscriptionId = $id;
        $this->editName = $sub->name;
        $this->editAmount = $sub->amount;
        $this->editBillingCycle = $sub->billing_cycle;
        $this->editNextRenewalDate = $sub->next_renewal_date->format('Y-m-d');
        $this->editIsActive = $sub->is_active;
    }

    public function updateSubscription()
    {
        $userId = Auth::id();

        $this->validate([
            'editName' => 'required|string|max:255',
            'editAmount' => 'required|numeric|min:0.01',
            'editBillingCycle' => 'required|string|in:daily,weekly,monthly,yearly',
            'editNextRenewalDate' => 'required|date',
        ]);

        $sub = PersonalSubscription::findOrFail($this->editingSubscriptionId);
        $sub->update([
            'name' => $this->editName,
            'amount' => $this->editAmount,
            'billing_cycle' => $this->editBillingCycle,
            'next_renewal_date' => $this->editNextRenewalDate,
            'is_active' => $this->editIsActive,
        ]);

        $this->cancelEdit();
        session()->flash('subscription-message', 'Subscription updated successfully.');
    }

    public function deleteSubscription($id)
    {
        PersonalSubscription::findOrFail($id)->delete();
        session()->flash('subscription-message', 'Subscription deleted successfully.');
    }

    public function toggleActive($id)
    {
        $sub = PersonalSubscription::findOrFail($id);
        $sub->update([
            'is_active' => !$sub->is_active
        ]);
    }

    public function cancelEdit()
    {
        $this->reset(['editingSubscriptionId', 'editName', 'editAmount', 'editBillingCycle', 'editNextRenewalDate', 'editIsActive']);
    }

    public function with()
    {
        $userId = Auth::id();
        $subscriptions = PersonalSubscription::with('user:id,name')
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . trim($this->search) . '%');
            })
            ->get();

        $totalActive = 0;
        $totalMonthlyCost = 0.0;
        $upcomingRenewal = null;
        $closestDiffDays = null;

        foreach ($subscriptions as $s) {
            // Remaining Days calculation
            $renewalDate = Carbon::parse($s->next_renewal_date)->startOfDay();
            $today = Carbon::today();
            $s->days_left = $today->diffInDays($renewalDate, false);

            if ($s->is_active) {
                $totalActive++;
                
                // Monthly Equivalent Cost
                switch ($s->billing_cycle) {
                    case 'daily':
                        $totalMonthlyCost += ($s->amount * 30);
                        break;
                    case 'weekly':
                        $totalMonthlyCost += ($s->amount * 52 / 12);
                        break;
                    case 'monthly':
                        $totalMonthlyCost += $s->amount;
                        break;
                    case 'yearly':
                        $totalMonthlyCost += ($s->amount / 12);
                        break;
                }

                // Upcoming Renewal computation (only for active subs where renewal is today or in future)
                if ($s->days_left >= 0) {
                    if ($upcomingRenewal === null || $s->days_left < $closestDiffDays) {
                        $upcomingRenewal = $s;
                        $closestDiffDays = $s->days_left;
                    }
                }
            }
        }

        return [
            'subscriptions' => $subscriptions,
            'totalActive' => $totalActive,
            'totalMonthlyCost' => (float)$totalMonthlyCost,
            'upcomingRenewal' => $upcomingRenewal,
        ];
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen">
    <!-- Header Section -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Subscriptions Management</h1>
            <p class="text-slate-500 mt-1">Track and manage your scheduled renewals and periodic bills</p>
        </div>
    </header>

    <!-- Metrics Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-indigo-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Active Subscriptions</p>
            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $totalActive }}</p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-rose-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Est. Monthly Cost</p>
            <p class="text-2xl font-bold text-slate-900 mt-2" x-text="showBalances ? '₹{{ number_format($totalMonthlyCost, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-emerald-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Next Upcoming Renewal</p>
            @if($upcomingRenewal)
                <div class="mt-2">
                    <p class="text-base font-bold text-slate-800 truncate">{{ $upcomingRenewal->name }}</p>
                    <p class="text-xs font-semibold text-emerald-600 mt-0.5">
                        @if($upcomingRenewal->days_left == 0)
                            Renews today!
                        @elseif($upcomingRenewal->days_left == 1)
                            Renews tomorrow
                        @else
                            Renews in {{ $upcomingRenewal->days_left }} days ({{ $upcomingRenewal->next_renewal_date->format('M d, Y') }})
                        @endif
                    </p>
                </div>
            @else
                <p class="text-sm font-semibold text-slate-400 mt-2.5">No upcoming renewals found</p>
            @endif
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Form Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden lg:col-span-1">
            <div class="p-6 text-white bg-gradient-to-br from-indigo-600 to-indigo-700">
                <h3 class="text-lg font-bold">{{ $editingSubscriptionId ? 'Edit Subscription' : 'Add Subscription' }}</h3>
                <p class="text-xs text-indigo-200 mt-1">Keep track of automatic payment cycles and dates</p>
            </div>
            
            <div class="p-6">
                @if(session()->has('subscription-message'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-600 rounded-xl text-sm font-semibold border border-emerald-100">
                        {{ session('subscription-message') }}
                    </div>
                @endif

                @if($editingSubscriptionId)
                    <form wire:submit="updateSubscription" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Subscription Name</label>
                            <input type="text" wire:model="editName"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. Netflix, Spotify">
                            @error('editName') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Billing Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="editAmount"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. 649">
                            @error('editAmount') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Billing Cycle</label>
                            <select wire:model="editBillingCycle" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            @error('editBillingCycle') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Next Renewal Date</label>
                            <input type="date" wire:model="editNextRenewalDate"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                            @error('editNextRenewalDate') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
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
                    <form wire:submit="createSubscription" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Subscription Name</label>
                            <input type="text" wire:model="name"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. Amazon Prime, Netflix">
                            @error('name') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Billing Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="amount"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800"
                                placeholder="e.g. 199">
                            @error('amount') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Billing Cycle</label>
                            <select wire:model="billing_cycle" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            @error('billing_cycle') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Next Renewal Date</label>
                            <input type="date" wire:model="next_renewal_date"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold text-slate-800">
                            @error('next_renewal_date') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2 py-2">
                            <input type="checkbox" wire:model="is_active" id="isActive" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <label for="isActive" class="text-sm font-bold text-slate-600 select-none">Active (Send reminders)</label>
                        </div>

                        <button type="submit"
                            class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-2">
                            Add Subscription
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
                    placeholder="Search subscriptions by name..."
                    class="w-full border-none focus:ring-0 text-sm font-medium text-slate-800 p-0">
            </div>

            <!-- Subscriptions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($subscriptions as $s)
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col justify-between space-y-4 relative group hover:shadow-md hover:border-slate-200 transition-all">
                        <!-- Top Details -->
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-base font-bold text-slate-800 tracking-tight">{{ $s->name }}</h4>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $s->is_active ? 'bg-indigo-50 text-indigo-600' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $s->billing_cycle }}
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500 block mt-1">
                                    Added by: {{ $s->creator_name }}
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="editSubscription({{ $s->id }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-all" title="Edit Subscription">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button wire:click="deleteSubscription({{ $s->id }})" onclick="confirm('Are you sure you want to delete this subscription?') || event.stopImmediatePropagation()" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-50 rounded-lg transition-all" title="Delete Subscription">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Mid Content: Cost & Renewal Status -->
                        <div class="flex justify-between items-baseline pt-2">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Billing Rate</p>
                                <p class="text-xl font-black text-slate-800 mt-0.5" x-text="showBalances ? '₹{{ number_format($s->amount, 2) }}' : '₹ ••••'"></p>
                            </div>
                            
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Next Renewal</p>
                                <p class="text-xs font-bold mt-0.5 text-slate-700">{{ $s->next_renewal_date->format('M d, Y') }}</p>
                            </div>
                        </div>

                        <!-- Bottom: Active Toggle and Days Remaining -->
                        <div class="flex justify-between items-center pt-3 border-t border-slate-50 text-[10px] font-bold uppercase tracking-wider">
                            <button wire:click="toggleActive({{ $s->id }})" class="flex items-center gap-1.5 transition-colors focus:outline-none">
                                <span class="h-2 w-2 rounded-full {{ $s->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' }}"></span>
                                <span class="{{ $s->is_active ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $s->is_active ? 'Active' : 'Paused' }}
                                </span>
                            </button>

                            @if($s->is_active)
                                @if($s->days_left == 0)
                                    <span class="text-rose-600">Renews today!</span>
                                @elseif($s->days_left == 1)
                                    <span class="text-amber-600">Renews tomorrow</span>
                                @elseif($s->days_left > 1)
                                    <span class="text-slate-500">{{ $s->days_left }} Days Left</span>
                                @else
                                    <span class="text-rose-500">Overdue ({{ abs($s->days_left) }}d)</span>
                                @endif
                            @else
                                <span class="text-slate-400">Suspended</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-12 rounded-3xl border border-slate-100 text-center col-span-1 md:col-span-2 flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <h4 class="text-lg font-bold text-slate-700">No active subscriptions found</h4>
                        <p class="text-sm text-slate-400 mt-1 max-w-xs">Use the config form on the left to catalog your first subscription or periodic bill.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
