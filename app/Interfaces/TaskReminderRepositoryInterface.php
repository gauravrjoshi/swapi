<?php

namespace App\Interfaces;

use App\Models\TaskReminder;
use Illuminate\Database\Eloquent\Collection;

interface TaskReminderRepositoryInterface
{
    public function create(array $data): TaskReminder;
    public function getAll(): Collection;
    public function updateLastTriggered(TaskReminder $reminder): void;
    public function deleteByTask(int $taskId): void;
}
