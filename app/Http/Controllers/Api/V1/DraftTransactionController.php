<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DraftTransaction;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;

class DraftTransactionController extends Controller
{
    /**
     * Display a listing of the draft transactions.
     */
    public function index(Request $request)
    {
        $drafts = DraftTransaction::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($draft) {
                $draft->receipt_url = $draft->hasMedia('receipt')
                    ? '/api/v1/draft-transactions/' . $draft->id . '/receipt'
                    : null;
                return $draft;
            });

        return response()->json($drafts);
    }

    /**
     * Store a newly created draft transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'required|string',
            'amount' => 'nullable|numeric',
            'date' => 'nullable|date_format:Y-m-d',
            'time' => 'nullable',
            'transaction_details' => 'nullable|string|max:255',
            'type' => 'nullable|in:credit,debit,transfer',
            'account_id' => 'nullable|exists:accounts,id',
            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id',
            'tag_id' => 'nullable|exists:tags,id',
            'description' => 'nullable|string',
            'ref_no' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:10240', // Max 10MB
        ]);

        $user = $request->user();

        $draft = DraftTransaction::updateOrCreate(
            ['uuid' => $validated['uuid'], 'user_id' => $user->id],
            Arr::except($validated, ['image'])
        );

        if ($request->hasFile('image')) {
            $draft->clearMediaCollection('receipt');
            $draft->addMediaFromRequest('image')->toMediaCollection('receipt');
        }

        $draft->receipt_url = $draft->hasMedia('receipt')
            ? '/api/v1/draft-transactions/' . $draft->id . '/receipt'
            : null;

        return response()->json($draft, 201);
    }

    /**
     * Update the specified draft transaction.
     */
    public function update(Request $request, $id)
    {
        $draft = $this->findDraft($request, $id);

        $validated = $request->validate([
            'amount' => 'nullable|numeric',
            'date' => 'nullable|date_format:Y-m-d',
            'time' => 'nullable',
            'transaction_details' => 'nullable|string|max:255',
            'type' => 'nullable|in:credit,debit,transfer',
            'account_id' => 'nullable|exists:accounts,id',
            'from_account_id' => 'nullable|exists:accounts,id',
            'to_account_id' => 'nullable|exists:accounts,id',
            'tag_id' => 'nullable|exists:tags,id',
            'description' => 'nullable|string',
            'ref_no' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:10240',
        ]);

        $draft->update(Arr::except($validated, ['image']));

        if ($request->hasFile('image')) {
            $draft->clearMediaCollection('receipt');
            $draft->addMediaFromRequest('image')->toMediaCollection('receipt');
        }

        $draft->receipt_url = $draft->hasMedia('receipt')
            ? '/api/v1/draft-transactions/' . $draft->id . '/receipt'
            : null;

        return response()->json($draft);
    }

    /**
     * Remove the specified draft transaction.
     */
    public function destroy(Request $request, $id)
    {
        $draft = $this->findDraft($request, $id);
        $draft->delete();

        return response()->json(['message' => 'Draft transaction deleted successfully.']);
    }

    /**
     * Confirm/promote draft transaction to a real transaction.
     */
    public function confirm(Request $request, $id, TransactionService $transactionService)
    {
        $draft = $this->findDraft($request, $id);

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
            'tag_id' => 'nullable|integer',
            'tag' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Custom validation logic per entry type
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

        // Use service to create transaction and update balances
        $transaction = $transactionService->createTransaction($validated);

        // Move media if exists
        $mediaItem = $draft->getFirstMedia('receipt');
        if ($mediaItem) {
            $mediaItem->move($transaction, 'receipt');
        }

        // Delete/Confirm draft
        $draft->delete();

        // Append receipt url (private API endpoint) to response
        $transaction->receipt_url = $transaction->hasMedia('receipt')
            ? '/api/v1/transactions/' . $transaction->id . '/receipt'
            : null;

        return response()->json($transaction, 201);
    }

    /**
     * Stream a draft's receipt attachment — only accessible by the owner.
     */
    public function receipt(Request $request, $id)
    {
        $draft = $this->findDraft($request, $id);

        $media = $draft->getFirstMedia('receipt');
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
     * Find a draft transaction by numeric ID or UUID.
     */
    private function findDraft(Request $request, $id)
    {
        return DraftTransaction::where('user_id', $request->user()->id)
            ->where(function ($query) use ($id) {
                if (is_numeric($id)) {
                    $query->where('id', $id)->orWhere('uuid', $id);
                } else {
                    $query->where('uuid', $id);
                }
            })
            ->firstOrFail();
    }
}
