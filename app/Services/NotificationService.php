<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $messaging;

    public function __construct()
    {
        // Only initialize Firebase if we are NOT in the local environment
        if (!app()->isLocal()) {
            try {
                $this->messaging = app(Messaging::class);
            } catch (\Throwable $e) {
                // If it fails to initialize (e.g. missing JSON), we log it but don't crash the app
                Log::error("Firebase failed to initialize: " . $e->getMessage());
            }
        }
    }

    /**
     * Send a notification to a specific user.
     *
     * @param string $title
     * @param string $body
     * @param array $data Optional data to send with the notification
     * @return bool
     */
    public function sendToUser($user, string $title, string $body, array $data = []): bool
    {
        // 1. Save notification log to the database
        try {
            \App\Models\DatabaseNotification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'type' => $data['type'] ?? 'general',
                'data' => $data,
                'read_at' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to save notification to database: " . $e->getMessage());
        }

        // If in local environment, just log the notification and return success
        if (app()->isLocal()) {
            Log::channel('slack')->info("Local Environment: Notification would be sent to User ID {$user->id}. Title: {$title}, Body: {$body}");
            return true;
        }

        // if (!$this->messaging) {
        //     Log::channel('slack')->warning("Firebase Messaging is not configured or initialized. Notification skipped.");
        //     return false;
        // }

        if (!$user->fcm_token) {
            Log::channel('slack')->info("Attempted to send notification to User ID {$user->id} but no FCM token was found.");
            return false;
        }

        $notification = Notification::create($title, $body);

        $message = CloudMessage::new()
            ->toToken($user->fcm_token)
            ->withNotification($notification)
            ->withData($data);

        try {
            $this->messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::channel('slack')->info("Failed to send FCM notification to User ID {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Broadcast a notification to all users except the excluded ones.
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $excludeUserIds
     * @return void
     */
    public function broadcast(string $title, string $body, array $data = [], array $excludeUserIds = []): void
    {
        $recipients = User::query()
            ->whereNotNull('fcm_token')
            ->whereNotIn('id', $excludeUserIds)
            ->get();

        foreach ($recipients as $recipient) {
            $this->sendToUser($recipient, $title, $body, $data);
        }
    }
}
