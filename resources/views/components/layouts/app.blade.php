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
    <body class="bg-slate-50 font-sans antialiased text-slate-900">
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
                    <div class="flex items-center gap-2">
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
    </body>
</html>
