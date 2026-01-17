<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'type',
        'times',
        'timezone',
        'last_triggered_at',
    ];

    protected $casts = [
        'times' => 'array',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get the task that owns the reminder.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
