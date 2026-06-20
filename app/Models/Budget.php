<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
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

    /**
     * Relationship with the User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
