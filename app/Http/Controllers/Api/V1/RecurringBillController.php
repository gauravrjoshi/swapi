<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RecurringBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringBillController extends Controller
{
    /**
     * Display a listing of recurring bills.
     */
    public function index(Request $request): JsonResponse
    {
        $bills = RecurringBill::with('user:id,name')->get();
        return response()->json($bills);
    }

    /**
     * Store or update a recurring bill.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:recurring_bills,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|string|in:income,expense',
            'frequency' => 'required|string|in:daily,weekly,monthly,yearly',
            'next_due_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $id = $validated['id'] ?? null;
        unset($validated['id']);

        if ($id) {
            $bill = RecurringBill::findOrFail($id);
            $bill->update($validated);
        } else {
            $bill = RecurringBill::create(array_merge($validated, ['user_id' => $userId]));
        }

        $bill->load('user:id,name');

        return response()->json($bill, 201);
    }

    /**
     * Remove the specified recurring bill.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $bill = RecurringBill::findOrFail($id);
        $bill->delete();

        return response()->json(null, 204);
    }
}
