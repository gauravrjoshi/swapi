<?php

namespace App\Notifications;

use Illuminate\Notifications\Notifiable;

/**
 * A simple notifiable target that routes all Slack notifications
 * to the configured SLACK_WEBHOOK_URL environment variable.
 *
 * Usage:
 *   Notification::send(new SlackChannel(), new SomeSlackNotification(...));
 */
class SlackChannel
{
    use Notifiable;

    /**
     * Route the Slack notification to the configured channel.
     * The bot token is read automatically from services.slack.notifications.bot_user_oauth_token
     */
    public function routeNotificationForSlack(): string
    {
        return config('services.slack.notifications.channel', '#support-tickets');
    }
}
