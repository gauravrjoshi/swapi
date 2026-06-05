<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToUnid;

class Account extends Model
{
    use HasFactory, BelongsToUnid, SoftDeletes;

    protected $fillable = [
        'name',
        'balance',
        'initial_balance',
        'is_savings',
        'user_id',
        'bank_name',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'branch_address',
        'account_type',
    ];

    protected $appends = ['user_name'];

    public function getUserNameAttribute()
    {
        return $this->user?->name;
    }

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
