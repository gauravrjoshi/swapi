<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get dashboard metrics.
     *
     * Two modes:
     *   1. "All Family" (no user_id): aggregated data for the entire UNID workspace.
     *      Available to ALL authenticated users.
     *   2. "Individual" (?user_id=X): data scoped to a specific family member.
     *      UNID-scoped — only members within the same workspace can be viewed.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $authUser = $request->user();

        // Enforce Spatie roles & permissions check (passing 'web' guard explicitly to bypass Sanctum mismatch)
        if (!$authUser->hasPermissionTo('view_all_dashboards', 'web') && !$authUser->hasRole('admin', 'web')) {
            // Force individual view scoped strictly to the requesting user
            $userId = $authUser->id;
        } else {
            // Default: All Family view (null = workspace-wide aggregated data)
            $userId = null;

            if ($request->has('user_id') && $request->user_id !== null && $request->user_id !== '') {
                $requestedId = (int) $request->user_id;

                // Restrict target query to the same UNID workspace
                $targetUser = User::where('unid', $authUser->unid)
                    ->where('id', $requestedId)
                    ->first();

                // If target is valid, use their ID; otherwise fall back to requester's own data
                $userId = $targetUser ? $targetUser->id : $authUser->id;
            }
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $data = $this->dashboardService->getDashboardData($userId, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
