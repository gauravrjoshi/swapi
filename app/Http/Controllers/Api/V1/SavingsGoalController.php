<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavingsGoalController extends Controller
{
    /**
     * Display a listing of savings goals.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $goals = SavingsGoal::where('user_id', $userId)->get();
        return response()->json($goals);
    }

    /**
     * Store or update a savings goal.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:savings_goals,id',
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0',
            'current_amount' => 'sometimes|numeric|min:0',
            'target_date' => 'nullable|date',
        ]);

        $id = $validated['id'] ?? null;
        unset($validated['id']);

        if ($id) {
            $goal = SavingsGoal::where('user_id', $userId)->findOrFail($id);
            $goal->update($validated);
        } else {
            $goal = SavingsGoal::create(array_merge($validated, ['user_id' => $userId]));
        }

        return response()->json($goal, 201);
    }

    /**
     * Remove the specified savings goal.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $goal = SavingsGoal::where('user_id', $userId)->findOrFail($id);
        $goal->delete();

        return response()->json(null, 204);
    }
}
