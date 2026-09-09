<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    public const TYPE_IMAGE =
        'image';

    public const TYPE_FILE =
        'file';

    protected $fillable = [
        'message_id',
        'type',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'sort_order',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            Message::class
        );
    }
}
