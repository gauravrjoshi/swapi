<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTaskRequest;
use App\Http\Requests\Api\V1\UpdateTaskRequest;
use App\Http\Resources\Api\V1\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index(Request $request): JsonResponse
    {
        $tasks = $this->taskService->getUserTasks($request->user()->id);
        return $this->successResponse(TaskResource::collection($tasks));
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $task = $this->taskService->createTask($data);
        return $this->successResponse(new TaskResource($task), 'Task created successfully', 201);
    }

    public function show(Task $task): JsonResponse
    {
        // Policy check should be here ideally
        return $this->successResponse(new TaskResource($task->load('reminders')));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $updatedTask = $this->taskService->updateTask($task, $request->validated());
        return $this->successResponse(new TaskResource($updatedTask), 'Task updated successfully');
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task);
        return $this->successResponse(null, 'Task deleted successfully');
    }
}
