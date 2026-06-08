<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToUnid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DraftTransaction extends Model implements HasMedia
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, BelongsToUnid, InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'user_id',
        'amount',
        'date',
        'time',
        'transaction_details',
        'type',
        'account_id',
        'from_account_id',
        'to_account_id',
        'tag_id',
        'description',
        'ref_no',
        'status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id')->withTrashed();
    }

    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id')->withTrashed();
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id')->withTrashed();
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')
            ->singleFile();
    }
}
