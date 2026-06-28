<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AppVersionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController
{
    /**
     * GET /api/v1/app-version
     * Public — no auth required.
     * Returns current version settings for the Flutter app to check against.
     */
    public function index(): JsonResponse
    {
        $settings = AppVersionSetting::first();

        return response()->json([
            'success' => true,
            'data'    => [
                'latest_version'       => $settings->latest_version,
                'min_required_version' => $settings->min_required_version,
                'force_update'         => $settings->force_update,
                'update_message'       => $settings->update_message,
                'android_store_url'    => $settings->android_store_url,
                'ios_store_url'        => $settings->ios_store_url,
            ],
        ]);
    }

    /**
     * PUT /api/v1/admin/app-version
     * Admin only. Updates the version settings row.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latest_version'       => 'sometimes|string|max:20',
            'min_required_version' => 'sometimes|string|max:20',
            'force_update'         => 'sometimes|boolean',
            'update_message'       => 'sometimes|string|max:500',
            'android_store_url'    => 'sometimes|url|max:500',
            'ios_store_url'        => 'sometimes|url|max:500',
        ]);

        $settings = AppVersionSetting::first();
        $settings->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'App version settings updated successfully.',
            'data'    => [
                'latest_version'       => $settings->latest_version,
                'min_required_version' => $settings->min_required_version,
                'force_update'         => $settings->force_update,
                'update_message'       => $settings->update_message,
                'android_store_url'    => $settings->android_store_url,
                'ios_store_url'        => $settings->ios_store_url,
            ],
        ]);
    }
}
