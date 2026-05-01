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
        'type',
        'account_id',
        'from_account_id',
        'to_account_id',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mainAccount()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
