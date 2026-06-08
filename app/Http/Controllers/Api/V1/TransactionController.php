<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, TransactionService $transactionService)
    {
        $filters = [
            'search' => $request->search,
            'type' => $request->type,
            'user_id' => $request->user_id,
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
            'tag_id' => $request->tag_id,
        ];

        $transactions = $transactionService->getTransactions($request->user()->id, $filters);

        $sumsFilters = $filters;
        if (empty($sumsFilters['from_date']) && empty($sumsFilters['to_date'])) {
            $today = now()->format('Y-m-d');
            $sumsFilters['from_date'] = $today;
            $sumsFilters['to_date'] = $today;
        }
        $sums = $transactionService->getTransactionSums($request->user()->id, $sumsFilters);

        $filters_value = [
            'users' => User::select('id', 'name')->get(),
            'tags' => Tag::select('name', 'name')->get(),
            'types' => [
                ['value' => 'credit', 'label' => 'Credit'],
                ['value' => 'debit', 'label' => 'Debit'],
                ['value' => 'transfer', 'label' => 'Transfer'],
            ],
        ];

        return response()->json(array_merge($transactions->toArray(), [
            'filters_value' => $filters_value,
            'transaction_sums' => $sums,
        ]));
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
            'transaction_details' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'other_transaction_details' => 'nullable|string|max:255',
            'account_id' => 'nullable|exists:accounts,id',
            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'ref_no' => 'nullable|string|max:100|unique:transactions,ref_no',
            'order_id' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:50',
            'tag_id' => 'nullable|integer',
            'comment' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:10240', // Max 10MB
        ]);

        $user = $request->user();

        // Custom validation logic per entry
        if ($validated['type'] === 'transfer') {
            if (empty($validated['from_account_id'])) {
                throw ValidationException::withMessages(['from_account_id' => 'The source account is required for transfers.']);
            }
            if (empty($validated['to_account_id'])) {
                throw ValidationException::withMessages(['to_account_id' => 'The destination account is required for transfers.']);
            }

            $fromAccount = Account::find($validated['from_account_id']);
            $toAccount = Account::find($validated['to_account_id']);

            if ($fromAccount->user_id != $user->id && $toAccount->user_id !== $user->id) {
                throw ValidationException::withMessages(['from_account_id' => 'You must own at least one of the accounts in a transfer.']);
            }
            $validated['account_id'] = null;
        } else {
            if (empty($validated['account_id'])) {
                throw ValidationException::withMessages(['account_id' => 'The account is required.']);
            }
            $account = Account::find($validated['account_id']);
            if ($account->user_id != $user->id) {
                throw ValidationException::withMessages(['account_id' => 'You can only add transactions to your own accounts.']);
            }
            $validated['from_account_id'] = null;
            $validated['to_account_id'] = null;
        }

        if (empty($validated['transaction_details'])) {
            $validated['transaction_details'] = $validated['description'] ?? $validated['remarks'] ?? 'Transaction';
        }

        // Add user_id to validated data
        $validated['user_id'] = $user->id;

        $transaction = $transactionService->createTransaction($validated);

        if ($request->hasFile('image')) {
            $transaction->clearMediaCollection('receipt');
            $transaction->addMediaFromRequest('image')->toMediaCollection('receipt');
        }

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
     * Stream the receipt attachment — only accessible by the transaction owner.
     */
    public function receipt(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        if ((int) $transaction->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $media = $transaction->getFirstMedia('receipt');
        if (!$media) {
            abort(404);
        }

        $path = $media->getPath();
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction, TransactionService $transactionService)
    {
        if ((int) $transaction->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You do not have permission to update this transaction'], 403);
        }

        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'time' => 'required',
                'type' => 'required|in:credit,debit,transfer',
                'transaction_details' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',
                'other_transaction_details' => 'nullable|string|max:255',
                'account_id' => 'nullable|exists:accounts,id',
                'from_account_id' => 'nullable|exists:accounts,id',
                'to_account_id' => 'nullable|exists:accounts,id|different:from_account_id',
                'amount' => 'required|numeric|min:0.01',
                'ref_no' => 'nullable|string|max:100|unique:transactions,ref_no,' . $transaction->id,
                'order_id' => 'nullable|string|max:100',
                'remarks' => 'nullable|string|max:255',
                'tag' => 'nullable|string|max:50',
                'tag_id' => 'nullable|integer',
                'comment' => 'nullable|string|max:255',
                'image' => 'nullable|image|max:10240', // Max 10MB
            ]);

            $user = $request->user();

            // Custom validation logic
            if ($validated['type'] === 'transfer') {
                if (empty($validated['from_account_id'])) {
                    throw ValidationException::withMessages(['from_account_id' => 'The source account is required for transfers.']);
                }
                if (empty($validated['to_account_id'])) {
                    throw ValidationException::withMessages(['to_account_id' => 'The destination account is required for transfers.']);
                }

                $fromAccount = Account::find($validated['from_account_id']);
                $toAccount = Account::find($validated['to_account_id']);

                if ($fromAccount->user_id != $user->id && $toAccount->user_id !== $user->id) {
                    throw ValidationException::withMessages(['from_account_id' => 'You must own at least one of the accounts in a transfer.']);
                }
                $validated['account_id'] = null;
            } else {
                if (empty($validated['account_id'])) {
                    throw ValidationException::withMessages(['account_id' => 'The account is required.']);
                }
                $account = Account::find($validated['account_id']);
                if ($account->user_id != $user->id) {
                    throw ValidationException::withMessages(['account_id' => 'You can only add transactions to your own accounts.']);
                }
                $validated['from_account_id'] = null;
                $validated['to_account_id'] = null;
            }

            if (empty($validated['transaction_details'])) {
                $validated['transaction_details'] = $validated['description'] ?? $validated['remarks'] ?? $transaction->transaction_details;
            }

            $updatedTransaction = $transactionService->updateTransaction($transaction, $validated);

            if ($request->hasFile('image')) {
                $updatedTransaction->clearMediaCollection('receipt');
                $updatedTransaction->addMediaFromRequest('image')->toMediaCollection('receipt');
            }

            return response()->json($updatedTransaction);
        } catch (ValidationException $e) {
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
