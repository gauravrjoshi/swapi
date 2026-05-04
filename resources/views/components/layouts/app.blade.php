<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=1">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=1">

        @livewireStyles
    </head>
    <body class="bg-slate-50 font-sans antialiased text-slate-900" x-data="{ showBalances: $persist(true) }">
        @if(session()->has('impersonator_id'))
            <div class="bg-rose-600 text-white px-4 py-2 flex items-center justify-between sticky top-0 z-[100] shadow-lg">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm font-black uppercase tracking-widest">
                        Impersonating: <span class="underline decoration-2 underline-offset-4">{{ Auth::user()->name }}</span>
                    </p>
                </div>
                <a href="{{ route('stop-impersonating') }}" class="bg-white text-rose-600 px-4 py-1 rounded-lg text-xs font-black uppercase hover:bg-rose-50 transition-all shadow-sm">
                    Exit Impersonation
                </a>
            </div>
        @endif
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="w-full mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-8">
                        <a href="/dashboard" class="flex-shrink-0 flex items-center group transition-all transform active:scale-95">
                            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto" style="height: 48px; width: auto; object-fit: contain;">
                        </a>
                        <div class="hidden sm:flex sm:space-x-4">
                            <a href="/dashboard" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->is('dashboard') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">Dashboard</a>
                            <a href="/transactions" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->is('transactions') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">Transactions</a>
                            <a href="/accounts" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->is('accounts') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">Accounts</a>
                            <a href="/transactions/new" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->is('transactions/new') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">New Entry</a>
                            <a href="/tags" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->is('tags') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">Tags</a>
                            @if(Auth::check() && Auth::user()->is_admin)
                                <a href="/admin/users" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->is('admin/users') ? 'bg-slate-100 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}">Users</a>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Masking Toggle -->
                        <button @click="showBalances = !showBalances" 
                                class="p-2.5 rounded-xl border border-slate-200 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition-all flex items-center gap-2 group">
                            <svg x-show="showBalances" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg x-show="!showBalances" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.888 9.888L5.123 5.123M18.991 18.991L14.226 14.226" />
                            </svg>
                            <span class="text-xs font-black uppercase tracking-widest hidden md:block" x-text="showBalances ? 'Hide Balances' : 'Show Balances'"></span>
                        </button>

                        @auth
                        <div class="relative" x-data="{ open: false }" @click.away="open = false" @profile-updated.window="$wire.$refresh()">
                            <button @click="open = !open" class="focus:outline-none" style="display: flex; align-items: center; gap: 12px; padding: 4px 8px; border-radius: 9999px; border: none; background: transparent; cursor: pointer;">
                                <div style="height: 36px; width: 36px; background-color: #e8eaed; border-radius: 9999px; overflow: hidden; display: flex; align-items: center; justify-content: center; color: #5f6368;">
                                    @if(Auth::user()->profile_photo_path)
                                        <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}" style="height: 100%; width: 100%; object-fit: cover;">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" style="height: 22px; width: 22px;" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-size: 15px; font-weight: 500; color: #202124; line-height: 1; white-space: nowrap;">{{ Auth::user()->name }}</span>
                                    <span style="font-size: 14px; color: #5f6368; line-height: 1; white-space: nowrap;">({{ Auth::user()->is_admin ? 'admin' : 'user' }})</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" style="height: 16px; width: 16px; color: #5f6368; margin-left: 4px; transition: transform 0.2s;" :style="open ? 'transform: rotate(180deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 z-[60] overflow-hidden"
                                 style="display: none;">
                                
                                <div class="px-4 py-2 border-b border-slate-50 md:hidden">
                                    <p class="text-sm font-bold text-slate-900">{{ Auth::user()->name }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase">{{ Auth::user()->is_admin ? 'Admin' : 'User' }}</p>
                                </div>

                                <a href="/profile" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    My Profile
                                </a>

                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-rose-600 hover:bg-rose-50 transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <main>
            {{ $slot }}
        </main>

        @livewireScripts
        <!-- Alpine.js Persist Plugin -->
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    </body>
</html>
