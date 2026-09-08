<?php

namespace App\Models;

use App\Events\NotificationCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $dispatchesEvents = [
        'created' => NotificationCreated::class,
    ];

    public const TYPE_LIKE =
        'like';

    public const TYPE_FOLLOW =
        'follow';

    public const TYPE_REPLY =
        'reply';

    public const TYPE_MENTION =
        'mention';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class,
                'user_id'
            );
    }

    public function actor(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class,
                'actor_id'
            );
    }

    public function post(): BelongsTo
    {
        return $this
            ->belongsTo(
                Post::class
            );
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
