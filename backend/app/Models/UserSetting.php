<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'theme',
        'language',
        'allow_messages',
        'email_notifications',
        'push_notifications',
    ];

    protected function casts(): array
    {
        return [
            'allow_messages' => 'boolean',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
