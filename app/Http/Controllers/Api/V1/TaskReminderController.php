<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReminderRequest;
use App\Http\Resources\Api\V1\TaskReminderResource;
use App\Models\Task;
use App\Services\TaskReminderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class TaskReminderController extends Controller
{
    use ApiResponse;

    protected $reminderService;

    public function __construct(TaskReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    /**
     * Get all reminders for a task.
     */
    public function index(Task $task): JsonResponse
    {
        return $this->successResponse(
            TaskReminderResource::collection($task->reminders),
            'Reminders retrieved successfully'
        );
    }

    /**
     * Add a reminder to a task.
     */
    public function store(StoreReminderRequest $request, Task $task): JsonResponse
    {
        $reminder = $this->reminderService->addReminder($task, $request->validated());
        return $this->successResponse(new TaskReminderResource($reminder), 'Reminder added successfully', 201);
    }

    /**
     * Update a reminder.
     */
    public function update(\App\Http\Requests\Api\V1\UpdateReminderRequest $request, Task $task, $reminderId): JsonResponse
    {
        $reminder = $task->reminders()->findOrFail($reminderId);
        $updatedReminder = $this->reminderService->updateReminder($reminder, $request->validated());
        return $this->successResponse(new TaskReminderResource($updatedReminder), 'Reminder updated successfully');
    }

    /**
     * Delete a reminder.
     */
    public function destroy(Task $task, $reminderId): JsonResponse
    {
        $reminder = $task->reminders()->findOrFail($reminderId);
        $this->reminderService->deleteReminder($reminder);
        return $this->successResponse(null, 'Reminder deleted successfully');
    }
}
