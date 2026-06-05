<?php

namespace App\Services;

use App\Models\User;
use App\Models\SupportTicket;
use App\Notifications\NewSupportTicketSlackNotification;
use App\Notifications\SlackChannel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SupportTicketService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new support ticket.
     */
    public function createTicket(User $user, array $data, ?UploadedFile $screenshot): SupportTicket
    {
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'unid' => $user->unid,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'open',
            'priority' => $data['priority'] ?? 'medium',
        ]);

        if ($screenshot) {
            $ticket->addMedia($screenshot)->toMediaCollection('screenshot');
        }

        // Reload so the screenshot media URL is available for Slack
        $ticket->refresh();

        // Fire Slack alert (queued so it doesn't slow down the HTTP response)
        if (config('services.slack.notifications.bot_user_oauth_token')) {
            Notification::send(new SlackChannel(), new NewSupportTicketSlackNotification($ticket));
        }

        return $ticket;
    }

    /**
     * Get all tickets for a specific user.
     */
    public function getUserTickets(User $user): Collection
    {
        return SupportTicket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get specific ticket details.
     */
    public function getTicketDetails(User $user, int $id): SupportTicket
    {
        return SupportTicket::where('user_id', $user->id)
            ->findOrFail($id);
    }

    /**
     * Get all tickets in the system for admin.
     * Filtered to only show tickets where creator user's email is 'gauravjoshi.uk.in@gmail.com'.
     */
    public function getAllTickets(): Collection
    {
        return SupportTicket::whereHas('user', function ($query) {
            $query->where('email', 'gauravjoshi.uk.in@gmail.com');
        })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update status of a ticket and send push notification.
     */
    public function updateTicketStatus(int $id, string $status): SupportTicket
    {
        $ticket = SupportTicket::with('user')->findOrFail($id);
        $oldStatus = $ticket->status;
        $ticket->status = $status;
        $ticket->save();

        // If status actually changed, send push notification
        if ($oldStatus !== $status && $ticket->user) {
            $title = "Support Ticket Status Updated";
            $body = "Your ticket '{$ticket->title}' status is now: " . strtoupper(str_replace('_', ' ', $status)) . ".";
            $this->notificationService->sendToUser(
                $ticket->user,
                $title,
                $body,
                [
                    'type' => 'ticket_status_update',
                    'ticket_id' => (string) $ticket->id,
                    'status' => $status
                ]
            );
        }

        return $ticket;
    }
}
