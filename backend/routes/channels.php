<?php

use App\Models\ConversationMember;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel(
    'notifications.{userId}',
    function (
        User $user,
        int $userId
    ): bool {
        return $user->id ===
            $userId;
    }
);

Broadcast::channel(
    'conversations.{conversationId}',
    function (
        User $user,
        int $conversationId
    ): bool {
        return ConversationMember::query()
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'user_id',
                $user->id
            )
            ->exists();
    }
);

Broadcast::channel(
    'inbox.{userId}',
    function (
        User $user,
        int $userId
    ): bool {
        return $user->id ===
            $userId;
    }
);
