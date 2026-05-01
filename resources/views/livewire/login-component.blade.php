<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $email;
    public $password;

    public function login()
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-slate-50 p-6">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-3xl shadow-xl border border-slate-100">
        <div class="text-center">
            <img src="{{ asset('logo.png') }}" alt="Logo" class="h-16 w-auto mx-auto mb-6" style="height: 64px; width: auto; object-fit: contain;">
            <h2 class="mt-6 text-3xl font-extrabold text-slate-900 tracking-tight">Sign in to your account</h2>
            <p class="mt-2 text-sm text-slate-500">Welcome back! Please enter your details.</p>
            @if (session()->has('success'))
                <div
                    class="mt-8 p-4 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-2xl text-sm font-bold flex items-center gap-3 animate-in fade-in slide-in-from-top-2 duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <form wire:submit="login" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-slate-700 ml-1">Email address</label>
                        <input type="email" wire:model="email" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        @error('email') <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-bold text-slate-700 ml-1">Password</label>
                        <input type="password" wire:model="password" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold text-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 transform active:scale-95">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">

            </div>
        </div>
    </div>