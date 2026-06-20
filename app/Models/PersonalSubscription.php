<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalSubscription extends Model
{
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
