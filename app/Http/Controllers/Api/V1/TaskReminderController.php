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

    public function store(StoreReminderRequest $request, Task $task): JsonResponse
    {
        $reminder = $this->reminderService->addReminder($task, $request->validated());
        return $this->successResponse(new TaskReminderResource($reminder), 'Reminder added successfully', 201);
    }
}
