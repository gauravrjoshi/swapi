<?php

use Livewire\Volt\Component;
use App\Models\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    public function getUnreadCountProperty()
    {
        if (!Auth::check()) {
            return 0;
        }
        return Auth::user()->databaseNotifications()->whereNull('read_at')->count();
    }

    public function getNotificationsProperty()
    {
        if (!Auth::check()) {
            return collect();
        }
        return Auth::user()->databaseNotifications()->orderBy('created_at', 'desc')->limit(5)->get();
    }

    public function markAllAsRead()
    {
        if (!Auth::check()) return;

        Auth::user()->databaseNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        $this->dispatch('notifications-updated');
    }

    public function markAsRead($id)
    {
        if (!Auth::check()) return;

        Auth::user()->databaseNotifications()->where('id', $id)->update(['read_at' => now()]);
        $this->dispatch('notifications-updated');
    }

    #[On('notifications-updated')]
    public function refreshDropdown()
    {
        // Trigger a re-render
    }
};
?>

<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <!-- Bell Button -->
    <button @click="open = !open" 
            class="p-3 rounded-2xl border border-slate-200 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition-all flex items-center gap-2 group shadow-sm relative focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" 
             class="h-5 w-5 transition-transform duration-200 group-hover:scale-110 group-hover:rotate-6" 
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        
        <!-- Red Badge Count -->
        @if($this->unreadCount > 0)
            <span class="absolute -top-1 -right-1 h-5 min-w-[20px] px-1.5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                {{ $this->unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-3 w-80 md:w-96 bg-white rounded-[1.5rem] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 py-3 z-[60] overflow-hidden"
         style="display: none;">
         
         <div class="px-5 py-2.5 flex items-center justify-between border-b border-slate-100 bg-slate-50/50">
             <div class="flex items-center gap-2">
                 <span class="text-sm font-bold text-slate-800">Notifications</span>
                 @if($this->unreadCount > 0)
                     <span class="bg-rose-100 text-rose-600 text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                         {{ $this->unreadCount }} new
                     </span>
                 @endif
             </div>
             @if($this->unreadCount > 0)
                 <button wire:click="markAllAsRead" 
                         class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors focus:outline-none">
                     Mark all read
                 </button>
             @endif
         </div>

         <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
             @forelse($this->notifications as $n)
                 <div class="px-5 py-3.5 hover:bg-slate-50/80 transition-colors flex gap-3 relative group {{ !$n->read_at ? 'bg-indigo-50/10' : '' }}">
                     <!-- Indicator dot -->
                     @if(!$n->read_at)
                         <span class="absolute top-5 left-2 w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                     @endif
                     
                     <!-- Type Specific Icon -->
                     <div class="shrink-0 mt-0.5">
                         @if($n->type === 'budget_alert')
                             <span class="h-8 w-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                           d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                 </svg>
                             </span>
                         @elseif($n->type === 'low_balance_alerts')
                             <span class="h-8 w-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                           d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                 </svg>
                             </span>
                         @elseif($n->type === 'subscription' || $n->type === 'recurring_bill')
                             <span class="h-8 w-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                           d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                 </svg>
                             </span>
                         @else
                             <span class="h-8 w-8 rounded-xl bg-slate-50 text-slate-500 flex items-center justify-center border border-slate-100">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                           d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                 </svg>
                             </span>
                         @endif
                     </div>

                     <!-- Content -->
                     <div class="flex-1 min-w-0">
                         <div class="flex justify-between items-start gap-1">
                             <p class="text-xs font-bold text-slate-800 truncate">{{ $n->title }}</p>
                             <span class="text-[9px] text-slate-400 whitespace-nowrap">{{ $n->created_at->diffForHumans() }}</span>
                         </div>
                         <p class="text-xs text-slate-500 mt-0.5 line-clamp-2" 
                            x-text="showBalances ? {{ json_encode($n->body) }} : {{ json_encode(preg_replace('/(₹|\$)\s*\d+([.,]\d+)*/u', '$1 ••••', $n->body)) }}">
                         </p>
                     </div>

                     <!-- Single Item Read Button -->
                     @if(!$n->read_at)
                         <div class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity self-center">
                             <button wire:click="markAsRead('{{ $n->id }}')" 
                                     class="p-1 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 focus:outline-none" 
                                     title="Mark as read">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                 </svg>
                             </button>
                         </div>
                     @endif
                 </div>
             @empty
                 <div class="px-5 py-8 text-center">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                               d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                     </svg>
                     <p class="text-xs font-bold text-slate-500">No new notifications</p>
                     <p class="text-[10px] text-slate-400 mt-0.5">We'll alert you here when something happens</p>
                 </div>
             @endforelse
         </div>

         <!-- View All Link -->
         <div class="border-t border-slate-100 mt-2 pt-2 px-5">
             <a href="/notifications" 
                class="block w-full text-center py-2 text-xs font-black uppercase text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50/50 hover:bg-indigo-50 rounded-xl">
                 View All Notifications
             </a>
         </div>
    </div>
</div>
