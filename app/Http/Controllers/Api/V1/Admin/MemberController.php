<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of members sharing the Admin's UNID.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get all non-admin users. Scoping by UNID is automatically applied via BelongsToUnid global scope.
        $members = User::where('is_admin', false)->get();

        return $this->successResponse(UserResource::collection($members), 'Members retrieved successfully');
    }

    /**
     * Store a newly created member in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'unid' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    // Check globally if the UNID exists, bypassing the current user's UNID query scope
                    $exists = User::withoutGlobalScope('unid')->where('unid', $value)->exists();
                    if (!$exists) {
                        $fail('The selected UNID does not exist in the system.');
                    }
                }
            ]
        ]);

        $validated['password'] = bcrypt($validated['password']);
        
        // Force non-admin status
        $validated['is_admin'] = false;

        // Default to the creator admin's UNID if not explicitly provided
        if (!isset($validated['unid']) || is_null($validated['unid'])) {
            $validated['unid'] = $request->user()->unid;
        }

        $user = User::create($validated);

        return $this->successResponse(new UserResource($user), 'Member created successfully', 201);
    }

    /**
     * Update the specified member in storage.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Finds the user. Automatically scoped to the Admin's UNID via the BelongsToUnid trait.
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'unid' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    $exists = User::withoutGlobalScope('unid')->where('unid', $value)->exists();
                    if (!$exists) {
                        $fail('The selected UNID does not exist in the system.');
                    }
                }
            ]
        ]);

        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // If 'unid' is not provided in the request payload, keep the existing value
        if (!array_key_exists('unid', $validated)) {
            unset($validated['unid']);
        }

        $user->update($validated);

        return $this->successResponse(new UserResource($user), 'Member updated successfully');
    }

    /**
     * Remove the specified member from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // Scoped automatically to Admin's UNID
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return $this->errorResponse('You cannot delete your own account.', null, 400);
        }

        $user->delete();

        return $this->successResponse(null, 'Member deleted successfully');
    }
}
