<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = $request->user()->transactions()->latest()->paginate(10);
        return response()->json($transactions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'transaction_details' => 'required|string',
            'other_transaction_details' => 'nullable|string',
            'account' => 'required|string',
            'amount' => 'required|numeric',
            'ref_no' => 'nullable|string',
            'order_id' => 'nullable|string',
            'remarks' => 'nullable|string',
            'tag' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $transaction = $request->user()->transactions()->create($validated);

        return response()->json($transaction, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        if ($transaction->user_id !== request()->user()->id) {
            abort(403);
        }
        return response()->json($transaction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== request()->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'time' => 'required',
            'transaction_details' => 'required|string',
            'other_transaction_details' => 'nullable|string',
            'account' => 'required|string',
            'amount' => 'required|numeric',
            'ref_no' => 'nullable|string',
            'order_id' => 'nullable|string',
            'remarks' => 'nullable|string',
            'tag' => 'nullable|string',
            'comment' => 'nullable|string',
        ]);

        $transaction->update($validated);

        return response()->json($transaction);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        if ($transaction->user_id !== request()->user()->id) {
            abort(403);
        }

        $transaction->delete();

        return response()->json(null, 204);
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
