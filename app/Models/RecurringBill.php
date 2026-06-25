<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUnid;

class RecurringBill extends Model
{
    use BelongsToUnid;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'type',
        'frequency',
        'next_due_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_due_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'creator_name',
    ];

    /**
     * Get the name of the user who created the bill.
     */
    public function getCreatorNameAttribute(): string
    {
        return $this->user ? $this->user->name : '';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
