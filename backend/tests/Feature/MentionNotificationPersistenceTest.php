<?php

namespace Tests\Feature;

use App\Events\UserMentioned;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentionNotificationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_mentioned_event_creates_notification(): void
    {
        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $mentionedUser =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($actor)
                ->create([
                    'content' => "Xin chào @{$mentionedUser->username}",
                ]);

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $mentionedUser,
                actor: $actor
            )
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $mentionedUser->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_MENTION,

                'post_id' => $post->id,

                'read_at' => null,
            ]
        );
    }

    public function test_self_mention_does_not_create_notification(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'content' => "Test @{$user->username}",
                ]);

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $user,
                actor: $user
            )
        );

        $this->assertDatabaseCount(
            'notifications',
            0
        );
    }

    public function test_same_mention_event_does_not_create_duplicate_notification(): void
    {
        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $mentionedUser =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($actor)
                ->create();

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $mentionedUser,
                actor: $actor
            )
        );

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $mentionedUser,
                actor: $actor
            )
        );

        $notificationCount =
            Notification::query()
                ->where(
                    'user_id',
                    $mentionedUser->id
                )
                ->where(
                    'actor_id',
                    $actor->id
                )
                ->where(
                    'type',
                    Notification::TYPE_MENTION
                )
                ->where(
                    'post_id',
                    $post->id
                )
                ->count();

        $this->assertSame(
            1,
            $notificationCount
        );
    }

    public function test_one_post_can_notify_multiple_mentioned_users(): void
    {
        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $firstUser =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $secondUser =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($actor)
                ->create();

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $firstUser,
                actor: $actor
            )
        );

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $secondUser,
                actor: $actor
            )
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $firstUser->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_MENTION,

                'post_id' => $post->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $secondUser->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_MENTION,

                'post_id' => $post->id,
            ]
        );

        $this->assertDatabaseCount(
            'notifications',
            2
        );
    }

    public function test_same_user_can_be_mentioned_in_different_posts(): void
    {
        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $mentionedUser =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $firstPost =
            Post::factory()
                ->for($actor)
                ->create();

        $secondPost =
            Post::factory()
                ->for($actor)
                ->create();

        event(
            new UserMentioned(
                post: $firstPost,
                mentionedUser: $mentionedUser,
                actor: $actor
            )
        );

        event(
            new UserMentioned(
                post: $secondPost,
                mentionedUser: $mentionedUser,
                actor: $actor
            )
        );

        $notificationCount =
            Notification::query()
                ->where(
                    'user_id',
                    $mentionedUser->id
                )
                ->where(
                    'actor_id',
                    $actor->id
                )
                ->where(
                    'type',
                    Notification::TYPE_MENTION
                )
                ->count();

        $this->assertSame(
            2,
            $notificationCount
        );
    }

    public function test_deleting_mentioned_post_removes_notification(): void
    {
        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $mentionedUser =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($actor)
                ->create();

        event(
            new UserMentioned(
                post: $post,
                mentionedUser: $mentionedUser,
                actor: $actor
            )
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $mentionedUser->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_MENTION,

                'post_id' => $post->id,
            ]
        );

        $post->delete();

        $this->assertDatabaseMissing(
            'notifications',
            [
                'post_id' => $post->id,

                'type' => Notification::TYPE_MENTION,
            ]
        );
    }
}
