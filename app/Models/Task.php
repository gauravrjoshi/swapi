<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToUnid;

class Task extends Model
{
    use HasFactory, BelongsToUnid;

    protected $fillable = [
        'user_id',
        'title',
        'description',
    ];

    /**
     * Get the reminders for the task.
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class);
    }
}
