<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUnid;

class Budget extends Model
{
    use BelongsToUnid;

    protected $fillable = [
        'user_id',
        'tag_id',
        'tag',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tag_id' => 'integer',
    ];

    protected $appends = [
        'creator_name',
    ];

    /**
     * Get the name of the user who created the budget.
     */
    public function getCreatorNameAttribute(): string
    {
        return $this->user ? $this->user->name : '';
    }

    /**
     * Relationship with the User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
