<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/admin/device-info
 * Admin-only read-only view of all users' latest device telemetry.
 */
class DeviceInfoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::select([
                'id',
                'name',
                'email',
                'app_version',
                'app_build',
                'platform',
                'os_version',
                'device_model',
                'device_last_seen',
            ])
            ->orderByDesc('device_last_seen')
            ->get()
            ->map(fn (User $user) => [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'app_version'      => $user->app_version,
                'app_build'        => $user->app_build,
                'platform'         => $user->platform,
                'os_version'       => $user->os_version,
                'device_model'     => $user->device_model,
                'device_last_seen' => $user->device_last_seen,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }
}
