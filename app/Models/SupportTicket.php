<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToUnid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SupportTicket extends Model implements HasMedia
{
    use HasFactory, BelongsToUnid, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'unid',
        'title',
        'description',
        'status',
        'priority',
    ];

    /**
     * Get the user who created the support ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
