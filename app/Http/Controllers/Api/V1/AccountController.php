<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    protected $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $accounts = $this->accountService->getAccounts($request->user()->id);
        $accounts = $this->accountService->getAccounts();
        return response()->json($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'balance' => 'required|numeric',
            'is_savings' => 'boolean',
            'bank_name' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:255',
            'branch_address' => 'nullable|string|max:1000',
            'account_type' => 'nullable|string|in:savings,general,liability',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['initial_balance'] = $validated['balance'];

        $account = $this->accountService->createAccount($validated);

        return response()->json($account, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        if ($account->user_id !== request()->user()->id) {
            abort(403);
        }

        return response()->json($account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        if ($account->user_id !== request()->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'balance' => 'sometimes|required|numeric',
            'is_savings' => 'boolean',
            'bank_name' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:255',
            'branch_address' => 'nullable|string|max:1000',
            'account_type' => 'nullable|string|in:savings,general,liability',
        ]);

        $account = $this->accountService->updateAccount($account, $validated);

        return response()->json($account);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        if ($account->user_id !== request()->user()->id) {
            abort(403);
        }

        $this->accountService->deleteAccount($account);

        return response()->json(null, 204);
    }
}
