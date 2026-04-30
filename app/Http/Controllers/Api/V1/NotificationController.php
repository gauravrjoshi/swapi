<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationController extends Controller
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
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

        $notification = Notification::create($request->title, $request->body);

        $message = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification($notification);

        try {
            $this->messaging->send($message);
            return response()->json(['message' => 'Notification sent successfully.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to send notification.', 'error' => $e->getMessage()], 500);
        }
    }
}
