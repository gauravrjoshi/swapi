<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUnid;

class PersonalSubscription extends Model
{
    use BelongsToUnid;

    protected $table = 'personal_subscriptions';

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'billing_cycle',
        'next_renewal_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_renewal_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'creator_name',
    ];

    /**
     * Get the name of the user who created the subscription.
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
