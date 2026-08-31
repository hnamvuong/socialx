<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'display_name',
        'email',
        'password',
        'bio',
        'location',
        'website',
        'avatar_path',
        'cover_path',
        'is_private',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_private' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles'
        )->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('name', $role)
            ->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->whereIn('name', $roles)
            ->exists();
    }

    public function hasPermission(
        string $permission
    ): bool {
        return $this->roles()
            ->whereHas(
                'permissions',
                function ($query) use ($permission) {
                    $query->where(
                        'permissions.name',
                        $permission
                    );
                }
            )
            ->exists();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(
            Post::class
        );
    }

    public function likes(): HasMany
    {
        return $this->hasMany(
            Like::class
        );
    }

    public function likedPosts(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Post::class,
                'likes'
            )
            ->withTimestamps();
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(
            Repost::class
        );
    }

    public function repostedPosts(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Post::class,
                'reposts'
            )
            ->withTimestamps();
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(
            Bookmark::class
        );
    }

    public function bookmarkedPosts(): BelongsToMany
    {
        return $this
            ->belongsToMany(
                Post::class,
                'bookmarks'
            )
            ->withTimestamps();
    }
}
