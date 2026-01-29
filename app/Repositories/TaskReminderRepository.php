<?php

namespace App\Repositories;

use App\Interfaces\TaskReminderRepositoryInterface;
use App\Models\TaskReminder;
use Illuminate\Database\Eloquent\Collection;

class TaskReminderRepository implements TaskReminderRepositoryInterface
{
    public function create(array $data): TaskReminder
    {
        return TaskReminder::create($data);
    }

    public function getAll(): Collection
    {
        return TaskReminder::with('task')->get();
    }

    public function find(int $id): ?TaskReminder
    {
        return TaskReminder::find($id);
    }

    public function update(TaskReminder $reminder, array $data): TaskReminder
    {
        $reminder->update($data);
        return $reminder;
    }

    public function delete(TaskReminder $reminder): bool
    {
        return $reminder->delete();
    }

    public function updateLastTriggered(TaskReminder $reminder): void
    {
        $reminder->update(['last_triggered_at' => now()]);
    }

    public function deleteByTask(int $taskId): void
    {
        TaskReminder::where('task_id', $taskId)->delete();
    }
}
