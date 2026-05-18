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
     * Update the authenticated user's FCM token.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update(['fcm_token' => $request->token]);

        return response()->json(['message' => 'FCM token updated successfully.']);
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
