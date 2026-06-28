<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Update the authenticated user's FCM token and device info.
     *
     * New optional fields (all nullable — backwards-compatible with older clients):
     *   app_version   string  e.g. "1.0.0"
     *   app_build     string  e.g. "52"
     *   platform      string  "android" | "ios"
     *   os_version    string  e.g. "Android 14" / "iOS 17.4"
     *   device_model  string  e.g. "Samsung Galaxy S24"
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'token'        => 'required|string',
            'app_version'  => 'nullable|string|max:20',
            'app_build'    => 'nullable|string|max:10',
            'platform'     => 'nullable|string|in:android,ios',
            'os_version'   => 'nullable|string|max:50',
            'device_model' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token'        => $request->token,
            'app_version'      => $request->app_version,
            'app_build'        => $request->app_build,
            'platform'         => $request->platform,
            'os_version'       => $request->os_version,
            'device_model'     => $request->device_model,
            'device_last_seen' => now(),
        ]);

        return response()->json(['message' => 'Device info updated successfully.']);
    }

    /**
     * Send a notification to a specific user (for testing).
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $user = \App\Models\User::find($request->user_id);

        if (!$user->fcm_token) {
            return response()->json(['message' => 'User does not have an FCM token.'], 404);
        }

        $success = $this->notificationService->sendToUser(
            $user,
            $request->title,
            $request->body
        );

        if ($success) {
            return response()->json(['message' => 'Notification sent successfully.']);
        } else {
            return response()->json(['message' => 'Failed to send notification or user has no token.'], 500);
        }
    }
}
