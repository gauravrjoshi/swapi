<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PersonalSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalSubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $subscriptions = PersonalSubscription::where('user_id', $userId)->get();
        return response()->json($subscriptions);
    }

    /**
     * Store or update a subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'id' => 'nullable|integer|exists:personal_subscriptions,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string|in:daily,weekly,monthly,yearly',
            'next_renewal_date' => 'required|date',
            'is_active' => 'sometimes|boolean',
        ]);

        $id = $validated['id'] ?? null;
        unset($validated['id']);

        if ($id) {
            $subscription = PersonalSubscription::where('user_id', $userId)->findOrFail($id);
            $subscription->update($validated);
        } else {
            $subscription = PersonalSubscription::create(array_merge($validated, ['user_id' => $userId]));
        }

        return response()->json($subscription, 201);
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $subscription = PersonalSubscription::where('user_id', $userId)->findOrFail($id);
        $subscription->delete();

        return response()->json(null, 204);
    }
}
