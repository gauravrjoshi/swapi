<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    use ApiResponse;

    /**
     * List all family members sharing the same UNID workspace.
     *
     * Any authenticated user can call this endpoint.
     * The BelongsToUnid global scope automatically limits results to the
     * requester's UNID, so cross-family data is never exposed.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // BelongsToUnid scope automatically filters by the authenticated user's UNID.
        // We include all users (admin + members) except the requesting user themselves
        // so the frontend can show "other family members" to switch to.
        $family = User::where('id', '!=', $request->user()->id)->get();

        return $this->successResponse(
            UserResource::collection($family),
            'Family members retrieved successfully'
        );
    }
}
