<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DatabaseNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsHistoryController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $notifications = DatabaseNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark the specified notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->id;
        $notification = DatabaseNotification::where('user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json($notification);
    }

    /**
     * Mark all notifications of the user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        DatabaseNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
