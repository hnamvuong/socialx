<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_can_be_created(): void
    {
        $recipient =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($recipient)
                ->create();

        $notification =
            new Notification;

        $notification->user_id =
            $recipient->id;

        $notification->actor_id =
            $actor->id;

        $notification->type =
            Notification::TYPE_LIKE;

        $notification->post_id =
            $post->id;

        $notification->save();

        $this->assertDatabaseHas(
            'notifications',
            [
                'id' => $notification->id,

                'user_id' => $recipient->id,

                'actor_id' => $actor->id,

                'type' => 'like',

                'post_id' => $post->id,

                'read_at' => null,
            ]
        );
    }

    public function test_notification_relationships_work(): void
    {
        $recipient =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($recipient)
                ->create();

        $notification =
            new Notification;

        $notification->user_id =
            $recipient->id;

        $notification->actor_id =
            $actor->id;

        $notification->type =
            Notification::TYPE_REPLY;

        $notification->post_id =
            $post->id;

        $notification->save();

        $this->assertTrue(
            $notification
                ->user
                ->is($recipient)
        );

        $this->assertTrue(
            $notification
                ->actor
                ->is($actor)
        );

        $this->assertTrue(
            $notification
                ->post
                ->is($post)
        );
    }

    public function test_follow_notification_can_exist_without_post(): void
    {
        $recipient =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        $notification =
            new Notification;

        $notification->user_id =
            $recipient->id;

        $notification->actor_id =
            $actor->id;

        $notification->type =
            Notification::TYPE_FOLLOW;

        $notification->post_id =
            null;

        $notification->save();

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $recipient->id,

                'actor_id' => $actor->id,

                'type' => 'follow',

                'post_id' => null,
            ]
        );
    }

    public function test_notification_read_at_is_cast_to_datetime(): void
    {
        $recipient =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        $notification =
            new Notification;

        $notification->user_id =
            $recipient->id;

        $notification->actor_id =
            $actor->id;

        $notification->type =
            Notification::TYPE_FOLLOW;

        $notification->read_at =
            now();

        $notification->save();

        $notification->refresh();

        $this->assertNotNull(
            $notification->read_at
        );

        $this->assertTrue(
            $notification->isRead()
        );
    }
}
