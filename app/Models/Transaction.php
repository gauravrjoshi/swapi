<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'date',
        'time',
        'transaction_details',
        'other_transaction_details',
        'account',
        'amount',
        'ref_no',
        'order_id',
        'remarks',
        'tag',
        'comment',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
