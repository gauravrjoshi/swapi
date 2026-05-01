<?php

use Illuminate\Support\Facades\Route;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard_page');
    });

    Route::get('/transactions', function () {
        return view('all_transactions_page');
    });

    Route::get('/transactions/download', function (Illuminate\Http\Request $request) {
        $query = App\Models\Transaction::with(['mainAccount', 'fromAccount', 'toAccount', 'user'])->latest();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('transaction_details', 'like', '%' . $request->search . '%')
                  ->orWhere('tag', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->user) {
            $query->where('user_id', $request->user);
        }

        if ($request->from) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->to) {
            $query->whereDate('date', '<=', $request->to);
        }

        $transactions = $query->get();
        $filters = [
            'search' => $request->search,
            'type' => $request->type,
            'user' => $request->user,
            'from' => $request->from,
            'to' => $request->to,
        ];

        $pdf = Pdf::loadView('pdf.transactions', compact('transactions', 'filters'));
        return $pdf->download('transactions_report_' . now()->format('Ymd_His') . '.pdf');
    })->name('transactions.download');

    Route::get('/transactions/new', function () {
        return view('transaction_page');
    });

    Route::get('/transactions/{transaction}/edit', function (App\Models\Transaction $transaction) {
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
