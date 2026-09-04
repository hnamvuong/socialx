<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [];

    public function posts(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Post::class,
                'post_hashtags'
            )
            ->withTimestamps();
    }
}
