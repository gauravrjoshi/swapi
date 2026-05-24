<?php

namespace App\Jobs;

use App\Models\TaskReminder;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reminder;

    /**
     * Create a new job instance.
     */
    public function __construct(TaskReminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $task = $this->reminder->task;

        if ($task) {
            $user = User::find($task->user_id);
            if ($user) {
                $notificationService->sendToUser(
                    $user,
                    'Task Reminder',
                    "Reminder: {$task->title} is due!",
                    [
                        'task_id' => (string) $task->id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );
            }
        }

        Log::channel('slack')->info("Reminder: {$task->title} is due! (Time: " . now()->toTimeString() . ")");
    }
}
