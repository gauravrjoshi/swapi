<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class NewSupportTicketSlackNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly SupportTicket $ticket)
    {
    }

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $ticket = $this->ticket->load('user');
        $priority = strtolower($ticket->priority ?? 'medium');

        // Emoji and colour per priority
        $priorityEmoji = match ($priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };

        $screenshotUrl = $ticket->getFirstMediaUrl('screenshot');
        $createdAt = $ticket->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A');

        $message = (new SlackMessage())
            ->text("*New Support Ticket Received!* $priorityEmoji")
            ->headerBlock("🎫 New Support Ticket #" . $ticket->id)
            ->sectionBlock(function (SectionBlock $section) use ($ticket, $priority, $priorityEmoji, $createdAt) {
                $section->text(
                    "*Title:* {$ticket->title}\n" .
                    "*Priority:* {$priorityEmoji} " . ucfirst($priority) . "\n" .
                    "*Status:* 🔵 Open\n" .
                    "*Submitted by:* {$ticket->user?->name} (`{$ticket->user?->email}`)\n" .
                    "*Time:* {$createdAt}"
                );
            })
            ->dividerBlock()
            ->sectionBlock(function (SectionBlock $section) use ($ticket) {
                $section->text(
                    "*📝 Description:*\n" .
                    $ticket->description
                );
            });

        // Attach the screenshot as an image block if one exists
        if ($screenshotUrl) {
            $message->imageBlock(
                $screenshotUrl,
                '📎 Screenshot attached by user',
                fn($image) => $image->title('Screenshot'),
            );
        }

        return $message;
    }
}
