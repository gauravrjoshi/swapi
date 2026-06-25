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

<body class="bg-slate-50 font-sans antialiased text-slate-900"
    x-data="{ showBalances: $persist(true), sidebarOpen: false }">
    @if(session()->has('impersonator_id'))
        <div class="bg-rose-600 text-white px-4 py-2 flex items-center justify-between relative z-[100] shadow-lg">
            <div class="flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-sm font-black uppercase tracking-widest">
                    Impersonating: <span class="underline decoration-2 underline-offset-4">{{ Auth::user()->name }}</span>
                </p>
            </div>
            <a href="{{ route('stop-impersonating') }}"
                class="bg-white text-rose-600 px-4 py-1 rounded-lg text-xs font-black uppercase hover:bg-rose-50 transition-all shadow-sm">
                Exit Impersonation
            </a>
        </div>
    @endif

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden"
        @click="sidebarOpen = false" style="display: none;"></div>

    <div class="flex h-screen overflow-hidden bg-slate-50">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-[280px] bg-white border-r border-slate-200/60 shadow-[4px_0_24px_rgba(0,0,0,0.02)] flex flex-col transform transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0"
            :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
            <div class="h-20 flex items-center justify-between px-8 border-b border-slate-100/80 shrink-0">
                <a href="/dashboard"
                    class="flex-shrink-0 flex items-center group transition-all transform hover:scale-105 active:scale-95">
                    <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto drop-shadow-sm"
                        style="object-fit: contain;">
                </a>
                <!-- Mobile Close Button -->
                <button @click="sidebarOpen = false"
                    class="lg:hidden p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-5 py-8 space-y-2.5 overflow-y-auto">
                <div class="px-3 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase">Menu</div>

                <a href="/dashboard"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('dashboard') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('dashboard') ? 'text-white' : 'text-slate-400' }}" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Dashboard
                </a>

                <a href="/transactions"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('transactions') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('transactions') ? 'text-white' : 'text-slate-400' }}"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Transactions
                </a>

                <a href="/accounts"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('accounts') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('accounts') ? 'text-white' : 'text-slate-400' }}" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Accounts
                </a>

                <div class="py-2"></div>
                <div class="px-3 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase">Actions</div>

                <a href="/transactions/new"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('transactions/new') ? 'bg-[#56b6e9] text-white shadow-lg shadow-blue-500/20' : 'text-[#56b6e9] bg-blue-50 hover:bg-blue-100' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    New Entry
                </a>

                <div class="py-2"></div>
                <div class="px-3 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase">Settings</div>

                <a href="/tags"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('tags') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('tags') ? 'text-white' : 'text-slate-400' }}" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Tags
                </a>

                <a href="/budgets"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('budgets') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('budgets') ? 'text-white' : 'text-slate-400' }}" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Budgets
                </a>

                <a href="/subscriptions"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('subscriptions') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('subscriptions') ? 'text-white' : 'text-slate-400' }}" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Subscriptions
                </a>

                <a href="/recurring-bills"
                    class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('recurring-bills') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 {{ request()->is('recurring-bills') ? 'text-white' : 'text-slate-400' }}" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Recurring Bills
                </a>

                @if(Auth::check() && Auth::user()->is_admin)
                    <a href="/admin/users"
                        class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('admin/users') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 {{ request()->is('admin/users') ? 'text-white' : 'text-slate-400' }}" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        Users
                    </a>
                    <a href="/admin/roles"
                        class="flex items-center gap-3.5 px-4 py-3.5 rounded-2xl text-[15px] font-bold transition-all duration-200 {{ request()->is('admin/roles') ? 'bg-gradient-to-r from-[#ed760e] to-[#f4933e] text-white shadow-lg shadow-orange-500/20' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 {{ request()->is('admin/roles') ? 'text-white' : 'text-slate-400' }}" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Roles & Permissions
                    </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Top Header -->
            <header
                class="h-20 bg-white border-b border-slate-200/60 flex items-center justify-between px-6 lg:px-10 shrink-0 z-30 shadow-[0_4px_24px_rgba(0,0,0,0.01)]">
                <div class="flex items-center gap-4">
                    <!-- Mobile Menu Button -->
                    <button @click="sidebarOpen = true"
                        class="lg:hidden p-2.5 -ml-2 text-slate-500 hover:text-slate-900 hover:bg-slate-50 rounded-xl transition-all focus:ring-2 focus:ring-[#ed760e]/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="lg:hidden flex items-center">
                        <a href="/dashboard">
                            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}"
                                class="h-8 w-auto drop-shadow-sm">
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-4 lg:gap-6 ml-auto">
                    <!-- Masking Toggle -->
                    <button @click="showBalances = !showBalances"
                        class="p-3 rounded-2xl border border-slate-200 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition-all flex items-center gap-2 group shadow-sm">
                        <svg x-show="showBalances" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="!showBalances" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.888 9.888L5.123 5.123M18.991 18.991L14.226 14.226" />
                        </svg>
                    </button>

                    @auth
                        <livewire:notifications-dropdown />
                        
                        <div class="relative" x-data="{ open: false }" @click.away="open = false"
                            @profile-updated.window="$wire.$refresh()">
                            <button @click="open = !open"
                                class="focus:outline-none p-1.5 pl-3 border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-all rounded-[2rem] bg-white shadow-sm flex items-center gap-3">
                                <div class="hidden md:flex flex-col items-end">
                                    <span
                                        class="text-[14px] font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</span>
                                    <span
                                        class="text-[11px] font-black tracking-widest text-slate-400 uppercase leading-tight">{{ Auth::user()->is_admin ? 'Admin' : 'User' }}</span>
                                </div>
                                <div
                                    class="h-10 w-10 bg-slate-100 border border-slate-200 rounded-full overflow-hidden flex items-center justify-center text-slate-400 shrink-0">
                                    @if(Auth::user()->profile_photo_path)
                                        <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}"
                                            class="h-full w-full object-cover">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-3 w-56 bg-white rounded-[1.5rem] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-slate-100 py-2.5 z-[60] overflow-hidden"
                                style="display: none;">

                                <div class="px-5 py-3 border-b border-slate-50 md:hidden bg-slate-50/50">
                                    <p class="text-[15px] font-bold text-slate-900">{{ Auth::user()->name }}</p>
                                    <p class="text-[11px] font-black tracking-widest text-slate-400 uppercase mt-0.5">
                                        {{ Auth::user()->is_admin ? 'Admin' : 'User' }}</p>
                                </div>

                                <a href="/profile"
                                    class="flex items-center gap-3.5 px-5 py-3 text-[14px] font-bold text-slate-600 hover:bg-slate-50 hover:text-[#ed760e] transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    My Profile
                                </a>

                                <div class="h-px bg-slate-100 my-1 mx-4"></div>

                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit"
                                        class="w-full flex items-center gap-3.5 px-5 py-3 text-[14px] font-bold text-rose-500 hover:bg-rose-50 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            <main class="flex-1 overflow-y-auto w-full relative">
                <div class="absolute inset-0">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
    <!-- Alpine.js Persist Plugin -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
</body>

</html>