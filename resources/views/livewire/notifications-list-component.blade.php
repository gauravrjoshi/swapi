<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $filter = 'all'; // 'all', 'unread', 'read'

    // Reset pagination when search or filter changes
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function markAsRead($id)
    {
        if (!Auth::check()) return;
        
        Auth::user()->databaseNotifications()->where('id', $id)->update(['read_at' => now()]);
        $this->dispatch('notifications-updated');
        session()->flash('notification-message', 'Notification marked as read.');
    }

    public function markAsUnread($id)
    {
        if (!Auth::check()) return;
        
        Auth::user()->databaseNotifications()->where('id', $id)->update(['read_at' => null]);
        $this->dispatch('notifications-updated');
        session()->flash('notification-message', 'Notification marked as unread.');
    }

    public function deleteNotification($id)
    {
        if (!Auth::check()) return;
        
        Auth::user()->databaseNotifications()->where('id', $id)->delete();
        $this->dispatch('notifications-updated');
        session()->flash('notification-message', 'Notification deleted successfully.');
    }

    public function markAllAsRead()
    {
        if (!Auth::check()) return;
        
        Auth::user()->databaseNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('notifications-updated');
        session()->flash('notification-message', 'All notifications marked as read.');
    }

    public function deleteAllHistory()
    {
        if (!Auth::check()) return;
        
        Auth::user()->databaseNotifications()->delete();
        $this->dispatch('notifications-updated');
        session()->flash('notification-message', 'All notification history deleted.');
    }

    public function getCountsProperty()
    {
        if (!Auth::check()) {
            return ['all' => 0, 'unread' => 0, 'read' => 0];
        }

        $counts = Auth::user()->databaseNotifications()
            ->selectRaw('count(*) as total, sum(case when read_at is null then 1 else 0 end) as unread')
            ->first();

        $total = (int) ($counts->total ?? 0);
        $unread = (int) ($counts->unread ?? 0);
        $read = $total - $unread;

        return [
            'all' => $total,
            'unread' => $unread,
            'read' => $read,
        ];
    }

    #[On('notifications-updated')]
    public function refreshList()
    {
        // Trigger a re-render
    }

    public function with()
    {
        if (!Auth::check()) {
            return ['notifications' => collect()];
        }

        $query = Auth::user()->databaseNotifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->filter === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('body', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'notifications' => $query->orderBy('created_at', 'desc')->paginate(10),
            'counts' => $this->counts,
        ];
    }
};
?>

<div class="p-6 w-full mx-auto space-y-6 bg-slate-50 min-h-screen">
    <!-- Header Card -->
    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-8 text-white flex flex-col md:flex-row md:items-center justify-between gap-4" 
             style="background: linear-gradient(to bottom right, #4f46e5, #3730a3);">
            <div>
                <h2 class="text-2xl font-bold">Notifications Log</h2>
                <p style="color: #c7d2fe;" class="mt-1">View system alerts, budget updates, low balances, and scheduled reminders</p>
            </div>
            
            <div class="flex items-center gap-3">
                <button wire:click="markAllAsRead" 
                        @if($counts['unread'] === 0) disabled @endif
                        class="px-4 py-2 bg-white/10 hover:bg-white/20 disabled:opacity-40 disabled:hover:bg-white/10 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all border border-white/10 focus:outline-none">
                    Mark All As Read
                </button>
                <button wire:click="deleteAllHistory" 
                        @if($counts['all'] === 0) disabled @endif
                        class="px-4 py-2 bg-rose-600/80 hover:bg-rose-600 disabled:opacity-40 disabled:hover:bg-rose-600/80 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all focus:outline-none"
                        onclick="confirm('Are you sure you want to clear your notification history? This action cannot be undone.') || event.stopImmediatePropagation()">
                    Delete All History
                </button>
            </div>
        </div>

        <!-- Filter and Search controls -->
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Tabs Filter -->
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="$set('filter', 'all')" 
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all focus:outline-none {{ $filter === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                    All
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] {{ $filter === 'all' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $counts['all'] }}
                    </span>
                </button>
                
                <button wire:click="$set('filter', 'unread')" 
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all focus:outline-none {{ $filter === 'unread' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                    Unread
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] {{ $filter === 'unread' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $counts['unread'] }}
                    </span>
                </button>

                <button wire:click="$set('filter', 'read')" 
                        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all focus:outline-none {{ $filter === 'read' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/10' : 'bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                    Read
                    <span class="ml-1 px-2 py-0.5 rounded-full text-[10px] {{ $filter === 'read' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $counts['read'] }}
                    </span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="relative w-full md:w-80">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       wire:model.live="search" 
                       placeholder="Search notification logs..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-600/50 focus:border-indigo-600 shadow-sm transition-all" />
            </div>
        </div>

        <!-- Flash messages -->
        <div class="px-6 pt-4">
            @if(session()->has('notification-message'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm font-bold flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('notification-message') }}
                </div>
            @endif
        </div>

        <!-- Notification List -->
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $n)
                <div class="p-6 hover:bg-slate-50/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative group {{ !$n->read_at ? 'bg-indigo-50/5' : '' }}">
                    
                    <div class="flex gap-4 items-start flex-1 min-w-0">
                        <!-- Indicator dot -->
                        @if(!$n->read_at)
                            <span class="w-2 h-2 bg-rose-500 rounded-full mt-3 shrink-0" title="Unread"></span>
                        @else
                            <span class="w-2 h-2 bg-transparent rounded-full mt-3 shrink-0"></span>
                        @endif

                        <!-- Type Specific Icon -->
                        <div class="shrink-0 mt-1">
                            @if($n->type === 'budget_alert')
                                <span class="h-10 w-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </span>
                            @elseif($n->type === 'low_balance_alerts')
                                <span class="h-10 w-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </span>
                            @elseif($n->type === 'subscription' || $n->type === 'recurring_bill')
                                <span class="h-10 w-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </span>
                            @else
                                <span class="h-10 w-10 rounded-2xl bg-slate-50 text-slate-500 flex items-center justify-center border border-slate-100 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Content details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold text-slate-800">{{ $n->title }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-500">
                                    {{ str_replace('_', ' ', $n->type) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1"
                               x-text="showBalances ? {{ json_encode($n->body) }} : {{ json_encode(preg_replace('/(₹|\$)\s*\d+([.,]\d+)*/u', '$1 ••••', $n->body)) }}">
                            </p>
                            <span class="text-[10px] text-slate-400 mt-2 block font-medium">
                                Received {{ $n->created_at->format('M d, Y h:i A') }} ({{ $n->created_at->diffForHumans() }})
                            </span>
                        </div>
                    </div>

                    <!-- Row Actions -->
                    <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                        @if(!$n->read_at)
                            <button wire:click="markAsRead('{{ $n->id }}')" 
                                    class="p-2.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 rounded-xl transition-all focus:outline-none" 
                                    title="Mark as read">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @else
                            <button wire:click="markAsUnread('{{ $n->id }}')" 
                                    class="p-2.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 border border-transparent hover:border-amber-100 rounded-xl transition-all focus:outline-none" 
                                    title="Mark as unread">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10a8 8 0 018-8h2" />
                                </svg>
                            </button>
                        @endif

                        <button wire:click="deleteNotification('{{ $n->id }}')" 
                                class="p-2.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-100 rounded-xl transition-all focus:outline-none" 
                                title="Delete alert log"
                                onclick="confirm('Are you sure you want to delete this notification?') || event.stopImmediatePropagation()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="h-16 w-16 bg-slate-50 border border-slate-100 rounded-3xl flex items-center justify-center text-slate-400 mx-auto mb-4 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No matching notifications found</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">There are no notifications matching your search criteria or filter status. Try clearing your filters or search query.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($notifications->hasPages())
            <div class="p-6 border-t border-slate-100 bg-slate-50/30">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
