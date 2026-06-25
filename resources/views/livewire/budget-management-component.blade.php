<?php

use Livewire\Volt\Component;
use App\Models\Budget;
use App\Models\Transaction;
use App\Services\TagService;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $tag = '';
    public $amount = '';
    public $search = '';

    public $editingBudgetId = null;
    public $editTag = '';
    public $editAmount = '';

    public function createBudget(TagService $tagService)
    {
        $userId = Auth::id();

        $this->validate([
            'tag' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $exists = Budget::where('tag', $this->tag)->exists();
        if ($exists) {
            $this->addError('tag', 'Budget already exists for this category.');
            return;
        }

        $tagModel = $tagService->resolveTag($userId, null, $this->tag);
        $tagId = $tagModel?->id;

        Budget::create([
            'user_id' => $userId,
            'tag' => $this->tag,
            'tag_id' => $tagId,
            'amount' => $this->amount,
        ]);
        session()->flash('budget-message', 'Budget created successfully.');

        $this->reset(['tag', 'amount']);
    }

    public function editBudget($id)
    {
        $budget = Budget::findOrFail($id);
        $this->editingBudgetId = $id;
        $this->editTag = $budget->tag;
        $this->editAmount = $budget->amount;
    }

    public function updateBudget(TagService $tagService)
    {
        $userId = Auth::id();

        $this->validate([
            'editTag' => 'required|string|max:255',
            'editAmount' => 'required|numeric|min:0.01',
        ]);

        $duplicate = Budget::where('tag', $this->editTag)
            ->where('id', '!=', $this->editingBudgetId)
            ->first();

        if ($duplicate) {
            $this->addError('editTag', 'Budget already exists for this category.');
            return;
        }

        $tagModel = $tagService->resolveTag($userId, null, $this->editTag);
        $tagId = $tagModel?->id;

        $budget = Budget::findOrFail($this->editingBudgetId);
        $budget->update([
            'tag' => $this->editTag,
            'tag_id' => $tagId,
            'amount' => $this->editAmount,
        ]);

        $this->cancelEdit();
        session()->flash('budget-message', 'Budget updated successfully.');
    }
 
     public function deleteBudget($id)
     {
         Budget::findOrFail($id)->delete();
         session()->flash('budget-message', 'Budget deleted successfully.');
     }
 
     public function cancelEdit()
     {
         $this->reset(['editingBudgetId', 'editTag', 'editAmount']);
     }
 
     public function with(TagService $tagService)
     {
         $userId = Auth::id();
         $budgets = Budget::query()
             ->when($this->search, function ($q) {
                 $q->where('tag', 'like', '%' . trim($this->search) . '%');
             })
             ->get();
 
         $startOfMonth = now()->startOfMonth()->toDateString();
         $endOfMonth = now()->endOfMonth()->toDateString();
 
         foreach ($budgets as $b) {
             $spent = Transaction::query()
                 ->where('type', 'debit')
                 ->whereBetween('date', [$startOfMonth, $endOfMonth])
                 ->where(function ($query) use ($b) {
                     $query->where('tag', $b->tag);
                     if ($b->tag_id !== null) {
                         $query->orWhere('tag_id', $b->tag_id);
                     }
                 })
                 ->sum('amount');
 
             $b->spent = (float) $spent;
         }
 
         $tags = $tagService->getTags($userId);
         $tagColorMap = $tags->pluck('color', 'name')->all();
 
         $totalBudgetLimit = (float) $budgets->sum('amount');
         $totalBudgetSpent = (float) $budgets->sum('spent');
         $totalBudgetRemaining = $totalBudgetLimit - $totalBudgetSpent;
 
         return [
             'budgets' => $budgets,
             'tags' => $tags,
             'tagColorMap' => $tagColorMap,
             'totalBudgetLimit' => $totalBudgetLimit,
             'totalBudgetSpent' => $totalBudgetSpent,
             'totalBudgetRemaining' => $totalBudgetRemaining,
         ];
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen">
    <!-- Header Summary Section -->
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Budgets Overview</h1>
            <p class="text-slate-500 mt-1">Manage monthly spending targets and limits for your categories</p>
        </div>
    </header>

    <!-- Metrics Summary Widgets -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-indigo-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Monthly Limit</p>
            <p class="text-2xl font-bold text-slate-900 mt-2" x-text="showBalances ? '₹{{ number_format($totalBudgetLimit, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-rose-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Budgeted Spent</p>
            <p class="text-2xl font-bold text-slate-900 mt-2" x-text="showBalances ? '₹{{ number_format($totalBudgetSpent, 2) }}' : '₹ ••••'"></p>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 border-l-4 border-l-emerald-500">
            <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Remaining</p>
            <p class="text-2xl font-bold text-slate-900 mt-2"
               :class="showBalances ? '{{ $totalBudgetRemaining >= 0 ? 'text-emerald-600' : 'text-rose-600' }}' : 'text-slate-900'"
               x-text="showBalances ? '₹{{ number_format($totalBudgetRemaining, 2) }}' : '₹ ••••'"></p>
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Form Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden lg:col-span-1">
            <div class="p-6 text-white" style="background: linear-gradient(to bottom right, #4f46e5, #6366f1);">
                <h3 class="text-lg font-bold">{{ $editingBudgetId ? 'Edit Budget Target' : 'Create Budget Target' }}</h3>
                <p class="text-xs text-indigo-200 mt-1">Assign a maximum monthly limit to a specific category tag</p>
            </div>
            
            <div class="p-6">
                @if(session()->has('budget-message'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-600 rounded-xl text-sm font-semibold border border-emerald-100">
                        {{ session('budget-message') }}
                    </div>
                @endif
                @if(session()->has('budget-error'))
                    <div class="mb-4 p-3 bg-rose-50 text-rose-600 rounded-xl text-sm font-semibold border border-rose-100">
                        {{ session('budget-error') }}
                    </div>
                @endif

                @if($editingBudgetId)
                    <form wire:submit="updateBudget" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Select Tag</label>
                            <select wire:model="editTag" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-slate-800">
                                <option value="">Select a tag...</option>
                                @foreach($tags as $t)
                                    <option value="{{ $t->name }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('editTag') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Monthly Amount limit (₹)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="editAmount"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold"
                                placeholder="e.g. 5000">
                            @error('editAmount') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
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
                    <form wire:submit="createBudget" class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Select Tag</label>
                            <select wire:model="tag" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-medium text-slate-800">
                                <option value="">Select a tag...</option>
                                @foreach($tags as $t)
                                    <option value="{{ $t->name }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                            @error('tag') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase ml-1">Monthly Amount limit (₹)</label>
                            <input type="number" step="0.01" min="0.01" wire:model="amount"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 transition-all font-semibold"
                                placeholder="e.g. 15000">
                            @error('amount') <span class="text-xs text-rose-500 font-semibold pl-1">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit"
                            class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 mt-2">
                            Set Budget Limit
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Budget Progress Cards Section -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Search bar -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-3">
                <span class="text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live="search"
                    placeholder="Search active budgets by tag name..."
                    class="w-full border-none focus:ring-0 text-sm font-medium text-slate-800 p-0">
            </div>

            <!-- Budgets Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($budgets as $b)
                    @php
                        $tagColor = $tagColorMap[$b->tag] ?? '#6366f1';
                        $percent = $b->amount > 0 ? ($b->spent / $b->amount) * 100 : 0;
                        $clampedPercent = min($percent, 100);
                        $barColor = $percent >= 100 ? 'bg-rose-500' : ($percent >= 80 ? 'bg-amber-500' : 'bg-emerald-500');
                        $textColor = $percent >= 100 ? 'text-rose-600' : ($percent >= 80 ? 'text-amber-600' : 'text-emerald-600');
                        $bgColor = $percent >= 100 ? 'bg-rose-50' : ($percent >= 80 ? 'bg-amber-50' : 'bg-emerald-50');
                    @endphp
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col justify-between space-y-4 relative group hover:shadow-md hover:border-slate-200 transition-all">
                        <!-- Top details -->
                        <div class="flex justify-between items-start">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full flex-shrink-0" style="background-color: {{ $tagColor }}"></span>
                                <h4 class="text-base font-bold text-slate-800">{{ $b->tag }}</h4>
                            </div>
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="editBudget({{ $b->id }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-50 rounded-lg transition-all" title="Edit Budget limit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <button wire:click="deleteBudget({{ $b->id }})" onclick="confirm('Are you sure you want to delete this budget?') || event.stopImmediatePropagation()" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-50 rounded-lg transition-all" title="Delete Budget limit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Progress indicator metrics -->
                        <div class="space-y-1">
                            <div class="flex justify-between items-baseline text-slate-400">
                                <span class="text-xs font-bold uppercase tracking-wider">Consumed</span>
                                <span class="text-xs font-bold text-slate-600">
                                    <span class="text-sm font-black text-slate-800" x-text="showBalances ? '₹{{ number_format($b->spent, 2) }}' : '₹ ••••'"></span>
                                    / <span x-text="showBalances ? '₹{{ number_format($b->amount, 2) }}' : '₹ ••••'"></span>
                                </span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                <div class="{{ $barColor }} h-full transition-all duration-500" style="width: {{ $clampedPercent }}%"></div>
                            </div>
                            
                            <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest pt-1">
                                <span class="{{ $textColor }}">{{ number_format($percent, 0) }}% Used</span>
                                @if($percent < 100)
                                    <span class="text-emerald-600" x-text="showBalances ? '₹{{ number_format($b->amount - $b->spent, 2) }} Left' : '₹ •••• Left'"></span>
                                @endif
                            </div>
                        </div>

                        <!-- Alerts and Warnings -->
                        @if($percent >= 100)
                            <div class="p-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 justify-center {{ $bgColor }} {{ $textColor }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span x-text="showBalances ? 'Limit Exceeded by ₹{{ number_format($b->spent - $b->amount, 2) }}' : 'Limit Exceeded by ₹ ••••'"></span>
                            </div>
                        @elseif($percent >= 80)
                            <div class="p-2.5 rounded-xl text-xs font-bold uppercase tracking-wider flex items-center gap-1.5 justify-center {{ $bgColor }} {{ $textColor }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>Warning: Consumed over 80%</span>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white p-12 rounded-3xl border border-slate-100 text-center col-span-1 md:col-span-2 flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2-2 4 4m0-7v3h-3" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-lg font-bold text-slate-700">No active budgets found</h4>
                        <p class="text-sm text-slate-400 mt-1 max-w-xs">Use the configuration form on the left to set up your first category target.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
