<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_their_notifications(): void
    {
        $recipient =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

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

        Sanctum::actingAs(
            $recipient
        );

        $response =
            $this->getJson(
                '/api/notifications'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.notifications.0.id',
                $notification->id
            )
            ->assertJsonPath(
                'data.notifications.0.type',
                Notification::TYPE_LIKE
            )
            ->assertJsonPath(
                'data.notifications.0.actor.id',
                $actor->id
            )
            ->assertJsonPath(
                'data.notifications.0.actor.username',
                $actor->username
            )
            ->assertJsonPath(
                'data.notifications.0.post.id',
                $post->id
            );
    }

    public function test_user_cannot_see_another_users_notifications(): void
    {
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

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $firstNotification =
            new Notification;

        $firstNotification->user_id =
            $firstUser->id;

        $firstNotification->actor_id =
            $actor->id;

        $firstNotification->type =
            Notification::TYPE_FOLLOW;

        $firstNotification->post_id =
            null;

        $firstNotification->save();

        $secondNotification =
            new Notification;

        $secondNotification->user_id =
            $secondUser->id;

        $secondNotification->actor_id =
            $actor->id;

        $secondNotification->type =
            Notification::TYPE_FOLLOW;

        $secondNotification->post_id =
            null;

        $secondNotification->save();

        Sanctum::actingAs(
            $firstUser
        );

        $response =
            $this->getJson(
                '/api/notifications'
            );

        $response
            ->assertOk();

        $ids =
            collect(
                $response->json(
                    'data.notifications'
                )
            )
                ->pluck('id');

        $this->assertTrue(
            $ids->contains(
                $firstNotification->id
            )
        );

        $this->assertFalse(
            $ids->contains(
                $secondNotification->id
            )
        );
    }

    public function test_notifications_are_sorted_newest_first(): void
    {
        $recipient =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $older =
            new Notification;

        $older->user_id =
            $recipient->id;

        $older->actor_id =
            $actor->id;

        $older->type =
            Notification::TYPE_FOLLOW;

        $older->post_id =
            null;

        $older->created_at =
            now()->subHour();

        $older->updated_at =
            now()->subHour();

        $older->save();

        $newer =
            new Notification;

        $newer->user_id =
            $recipient->id;

        $newer->actor_id =
            $actor->id;

        $newer->type =
            Notification::TYPE_FOLLOW;

        $newer->post_id =
            null;

        $newer->save();

        Sanctum::actingAs(
            $recipient
        );

        $response =
            $this->getJson(
                '/api/notifications'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.notifications.0.id',
                $newer->id
            )
            ->assertJsonPath(
                'data.notifications.1.id',
                $older->id
            );
    }

    public function test_notification_endpoint_requires_authentication(): void
    {
        $this
            ->getJson(
                '/api/notifications'
            )
            ->assertUnauthorized();
    }

    public function test_notification_endpoint_returns_cursor_pagination(): void
    {
        $recipient =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        for (
            $index = 0;
            $index < 21;
            $index++
        ) {
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
        }

        Sanctum::actingAs(
            $recipient
        );

        $response =
            $this->getJson(
                '/api/notifications'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.notifications'
            )
            ->assertJsonPath(
                'data.pagination.per_page',
                20
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                true
            );

        $this->assertNotNull(
            $response->json(
                'data.pagination.next_cursor'
            )
        );
    }
}
