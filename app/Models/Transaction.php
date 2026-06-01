<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUnid;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, BelongsToUnid, LogsActivity;


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
        'tag_id',
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
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'from_account_running_balance' => 'decimal:2',
        'to_account_running_balance' => 'decimal:2',
    ];

    protected $appends = [
        'resolved_tag',
    ];

    protected static function booted()
    {
        static::saving(function ($transaction) {
            // Only synchronize if tag_id or tag has changed or if it is a new record.
            if ($transaction->isDirty('tag_id') || $transaction->isDirty('tag') || !$transaction->exists) {
                $tagService = app(\App\Services\TagService::class);
                $userId = $transaction->user_id;

                if (!$userId) {
                    if ($transaction->account_id) {
                        $userId = \App\Models\Account::find($transaction->account_id)?->user_id;
                    } elseif ($transaction->from_account_id) {
                        $userId = \App\Models\Account::find($transaction->from_account_id)?->user_id;
                    }
                }

                if ($userId) {
                    if ($transaction->isDirty('tag_id')) {
                        if ($transaction->tag_id !== null) {
                            $tag = $tagService->resolveTag($userId, $transaction->tag_id);
                            $transaction->tag = $tag?->name;
                        } else {
                            $transaction->tag = null;
                        }
                    } elseif ($transaction->isDirty('tag') || (!$transaction->exists && $transaction->tag_id === null)) {
                        if ($transaction->tag !== null && trim($transaction->tag) !== '') {
                            $tag = $tagService->resolveTag($userId, null, $transaction->tag);
                            $transaction->tag_id = $tag?->id;
                        } else {
                            $transaction->tag_id = null;
                        }
                    }
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function associatedTag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    public function getResolvedTagAttribute()
    {
        if ($this->tag_id === null) {
            return null;
        }
        $userId = $this->user_id;
        if (!$userId) {
            if ($this->account_id) {
                $userId = \App\Models\Account::find($this->account_id)?->user_id;
            } elseif ($this->from_account_id) {
                $userId = \App\Models\Account::find($this->from_account_id)?->user_id;
            }
        }
        if (!$userId) {
            return null;
        }
        return app(\App\Services\TagService::class)->resolveTag($userId, $this->tag_id);
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
        if (!$user)
            return false;
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
}
