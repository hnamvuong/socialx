<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function media(): HasMany
    {
        return $this
            ->hasMany(PostMedia::class)
            ->orderBy('sort_order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Post::class,
            'parent_post_id'
        );
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(
            Post::class,
            'root_post_id'
        );
    }

    public function replies(): HasMany
    {
        return $this
            ->hasMany(
                Post::class,
                'parent_post_id'
            )
            ->orderBy('created_at');
    }
}
