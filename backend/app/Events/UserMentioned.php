<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Post $post,
        public readonly User $mentionedUser,
        public readonly User $actor
    ) {}
}
