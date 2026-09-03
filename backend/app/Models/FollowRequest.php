<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING =
        'pending';

    protected $fillable = [];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requester_id'
        );
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'target_id'
        );
    }
}
