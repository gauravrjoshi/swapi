<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BudgetController extends Controller
{
    /**
     * Display a listing of the budgets with current month's spent amount.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $budgets = Budget::where('user_id', $userId)->get();

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        foreach ($budgets as $budget) {
            $spent = Transaction::query()
                ->where('user_id', $userId)
                ->where('type', 'debit')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where(function ($query) use ($budget) {
                    $query->where('tag', $budget->tag);
                    if ($budget->tag_id !== null) {
                        $query->orWhere('tag_id', $budget->tag_id);
                    }
                })
                ->sum('amount');

            $budget->spent = (float) $spent;
        }

        return response()->json($budgets);
    }

    /**
     * Store or update a budget (upsert behavior).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'tag' => 'required|string|max:255',
            'tag_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Find existing budget by tag or tag_id
        $budget = Budget::where('user_id', $userId)
            ->where(function ($query) use ($validated) {
                $query->where('tag', $validated['tag']);
                if (!empty($validated['tag_id'])) {
                    $query->orWhere('tag_id', $validated['tag_id']);
                }
            })
            ->first();

        if ($budget) {
            // Update existing budget limit
            $budget->update([
                'amount' => $validated['amount'],
                'tag_id' => $validated['tag_id'] ?? $budget->tag_id,
                'tag' => $validated['tag'], // Update name if it changed slightly
            ]);
        } else {
            // Create a new budget
            $budget = Budget::create([
                'user_id' => $userId,
                'tag_id' => $validated['tag_id'] ?? null,
                'tag' => $validated['tag'],
                'amount' => $validated['amount'],
            ]);
        }

        // Calculate and attach spent for the response
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $spent = Transaction::query()
            ->where('user_id', $userId)
            ->where('type', 'debit')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where(function ($query) use ($budget) {
                $query->where('tag', $budget->tag);
                if ($budget->tag_id !== null) {
                    $query->orWhere('tag_id', $budget->tag_id);
                }
            })
            ->sum('amount');

        $budget->spent = (float) $spent;

        return response()->json($budget, 201);
    }

    /**
     * Remove the specified budget limit.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $budget = Budget::where('user_id', $userId)->findOrFail($id);
        $budget->delete();

        return response()->json(null, 204);
    }
}
