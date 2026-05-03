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

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
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
