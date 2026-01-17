<?php

namespace App\Repositories;

use App\Interfaces\TaskRepositoryInterface;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task;
    }

    public function delete(Task $task): bool
    {
        return $task->delete();
    }

    public function find(int $id): ?Task
    {
        return Task::find($id);
    }

    public function getAllByUser(int $userId): Collection
    {
        return Task::where('user_id', $userId)->with('reminders')->get();
    }
}
