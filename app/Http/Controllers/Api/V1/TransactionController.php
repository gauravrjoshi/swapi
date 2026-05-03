<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, TransactionService $transactionService)
    {
        $transactions = $transactionService->getTransactions($request->user()->id, [
            'search' => $request->search,
            'type' => $request->type,
            'user_id' => $request->user_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ]);

        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, TransactionService $transactionService)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'type' => 'required|in:credit,debit,transfer',
            'transaction_details' => 'nullable|string',
            'description' => 'nullable|string',
            'other_transaction_details' => 'nullable|string',
            'account' => 'required|string',
            'account_id' => 'nullable|integer',
            'from_account_id' => 'nullable|integer',
            'to_account_id' => 'nullable|integer',
            'amount' => 'required|numeric',
            'ref_no' => 'nullable|string',
            'order_id' => 'nullable|string',
            'remarks' => 'nullable|string',
            'tag' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        if (empty($validated['transaction_details'])) {
            $validated['transaction_details'] = $validated['description'] ?? $validated['remarks'] ?? 'Transaction';
        }

        $transaction = $transactionService->createTransaction($validated);

        return response()->json($transaction, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction)
    {
        if ((int) $transaction->user_id !== (int) $request->user()->id) {
            abort(403);
        }
        return response()->json($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction, TransactionService $transactionService)
    {
        /* if ((int) $transaction->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You do not have permission to update this transaction'], 403);
        } */

        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'time' => 'required',
                'type' => 'required|in:credit,debit,transfer',
                'transaction_details' => 'nullable|string',
                'description' => 'nullable|string',
                'other_transaction_details' => 'nullable|string',
                'account' => 'required|string',
                'account_id' => 'nullable|integer',
                'from_account_id' => 'nullable|integer',
                'to_account_id' => 'nullable|integer',
                'amount' => 'required|numeric',
                'ref_no' => 'nullable|string',
                'order_id' => 'nullable|string',
                'remarks' => 'nullable|string',
                'tag' => 'nullable|string',
                'comment' => 'nullable|string',
            ]);

            if (empty($validated['transaction_details'])) {
                $validated['transaction_details'] = $validated['description'] ?? $validated['remarks'] ?? $transaction->transaction_details;
            }

            $updatedTransaction = $transactionService->updateTransaction($transaction, $validated);

            return response()->json($updatedTransaction);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Update failed', [
                'error' => $e->getMessage(),
                'id' => $transaction->id,
                'user_id' => $request->user()->id
            ]);
            return response()->json([
                'message' => 'Failed to update transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction, TransactionService $transactionService)
    {
        /* if ((int) $transaction->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You do not have permission to delete this transaction'], 403);
        } */

        try {
            $transactionService->deleteTransaction($transaction);
            return response()->noContent();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Delete failed', [
                'error' => $e->getMessage(),
                'id' => $transaction->id,
                'user_id' => $request->user()->id
            ]);
            return response()->json([
                'message' => 'Failed to delete transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\TransactionsImport($request->user()->id), $request->file('file'));

        return response()->json(['message' => 'Transactions imported successfully']);
    }
}
