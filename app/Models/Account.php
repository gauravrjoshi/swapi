<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'balance',
        'is_savings',
        'user_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'branch_address',
        'account_type',
    ];

    public function isGeneral(): bool
    {
        return $this->account_type === 'general';
    }

    public function isSavings(): bool
    {
        return $this->account_type === 'savings';
    }

    public function isLiability(): bool
    {
        return $this->account_type === 'liability';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'account_id')
            ->orWhere('from_account_id', $this->id)
            ->orWhere('to_account_id', $this->id);
    }
}
