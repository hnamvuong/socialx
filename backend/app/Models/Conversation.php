<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    public const TYPE_DIRECT =
        'direct';

    public const TYPE_GROUP =
        'group';

    protected $fillable = [
        'type',
        'direct_key',
        'title',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(
            ConversationMember::class
        );
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversation_members'
        )
            ->withPivot([
                'role',
                'joined_at',
            ])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class
        );
    }
}
