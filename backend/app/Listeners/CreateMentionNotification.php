<?php

namespace App\Listeners;

use App\Events\UserMentioned;
use App\Models\Notification;

class CreateMentionNotification
{
    public function handle(
        UserMentioned $event
    ): void {
        if ($event->mentionedUser->id === $event->actor->id) {
            return;
        }

        $alreadyExists =
            Notification::query()
                ->where(
                    'user_id',
                    $event
                        ->mentionedUser
                        ->id
                )
                ->where(
                    'actor_id',
                    $event
                        ->actor
                        ->id
                )
                ->where(
                    'type',
                    Notification::TYPE_MENTION
                )
                ->where(
                    'post_id',
                    $event
                        ->post
                        ->id
                )
                ->exists();

        if ($alreadyExists) {
            return;
        }

        $notification =
            new Notification;

        $notification->user_id =
            $event
                ->mentionedUser
                ->id;

        $notification->actor_id =
            $event
                ->actor
                ->id;

        $notification->type =
            Notification::TYPE_MENTION;

        $notification->post_id =
            $event
                ->post
                ->id;

        $notification->save();
    }
}
