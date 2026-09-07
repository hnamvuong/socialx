<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mention extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [];

    public function post(): BelongsTo
    {
        return $this->belongsTo(
            Post::class
        );
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'mentioned_user_id'
        );
    }
}
