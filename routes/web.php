<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard_page');
    });

    Route::get('/transactions/new', function () {
        return view('transaction_page');
    });

    Route::get('/transactions/{transaction}/edit', function (App\Models\Transaction $transaction) {
        if ($transaction->user_id !== Auth::id()) abort(403);
        return view('transaction_page', ['transaction' => $transaction]);
    });

    Route::get('/accounts', function () {
        return view('account_management_page');
    });

    Route::get('/profile', function () {
        return view('profile_page');
    });

    Route::get('/tags', function () {
        return view('tag_management_page');
    });

    Route::get('/admin/users', function () {
        if (!auth()->user()->is_admin) abort(403);
        return view('user_management_page');
    });
});

Route::get('/login', function () {
    return view('login_page');
})->name('login');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login')->with('success', 'You have been logged out successfully.');
});

// Since I don't have a login system ready in this task, I'll add a bypass for testing if needed
// or just assume the user will login via the existing API/auth.
