<?php

namespace App\Services;

use App\Interfaces\TaskReminderRepositoryInterface;
use App\Jobs\SendReminderJob;
use App\Models\Task;
use App\Models\TaskReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TaskReminderService
{
    protected $reminderRepository;

    public function __construct(TaskReminderRepositoryInterface $reminderRepository)
    {
        $this->reminderRepository = $reminderRepository;
    }

    /**
     * Add a reminder to a task.
     *
     * @param Task $task
     * @param array $data
     * @return TaskReminder
     */
    public function addReminder(Task $task, array $data): TaskReminder
    {
        $data['task_id'] = $task->id;
        return $this->reminderRepository->create($data);
    }

    /**
     * Update a reminder.
     *
     * @param TaskReminder $reminder
     * @param array $data
     * @return TaskReminder
     */
    public function updateReminder(TaskReminder $reminder, array $data): TaskReminder
    {
        return $this->reminderRepository->update($reminder, $data);
    }

    /**
     * Delete a reminder.
     *
     * @param TaskReminder $reminder
     * @return bool
     */
    public function deleteReminder(TaskReminder $reminder): bool
    {
        return $this->reminderRepository->delete($reminder);
    }

    /**
     * Check all reminders and dispatch jobs if due.
     * Run this every minute.
     *
     * @return void
     */
    public function checkAndDispatchReminders(): void
    {
        $reminders = $this->reminderRepository->getAll();

        foreach ($reminders as $reminder) {
            $this->processReminder($reminder);
        }
    }

    /**
     * Process individual reminder.
     *
     * @param TaskReminder $reminder
     * @return void
     */
    protected function processReminder(TaskReminder $reminder): void
    {
        $timezone = $reminder->timezone;
        $now = Carbon::now($timezone);
        $currentMinute = $now->format('H:i');

        // Check if reminder was already triggered in this minute (debounce)
        if ($reminder->last_triggered_at) {
            $lastTriggered = $reminder->last_triggered_at->setTimezone($timezone);
            if ($lastTriggered->format('H:i') === $currentMinute && $lastTriggered->isToday()) {
                return;
            }
        }

        if (in_array($currentMinute, $reminder->times)) {
            // Dispatch Job
            SendReminderJob::dispatch($reminder);

            // Update last triggered
            $this->reminderRepository->updateLastTriggered($reminder);

            Log::info("Dispatched reminder for Task ID: {$reminder->task_id} at {$currentMinute} ({$timezone})");
        }
    }
}
