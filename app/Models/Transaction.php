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
        'running_balance',
        'from_account_running_balance',
        'to_account_running_balance',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'from_account_running_balance' => 'decimal:2',
        'to_account_running_balance' => 'decimal:2',
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

    /**
     * Check if a user can manage (edit/delete) this transaction.
     * A user can only manage transactions involving accounts they own.
     */
    public function canBeManagedBy($user): bool
    {
        if (!$user) return false;
        $userId = is_object($user) ? $user->id : $user;

        if ($this->type === 'transfer') {
            // For transfers, if you own either account, you can manage it? 
            // The prompt says "latika can only add transactions entry related to it"
            // So if she owns the source or destination, she should be able to manage it.
            return ($this->fromAccount && $this->fromAccount->user_id == $userId) || 
                   ($this->toAccount && $this->toAccount->user_id == $userId);
        }

        return $this->mainAccount && $this->mainAccount->user_id == $userId;
    }
}
