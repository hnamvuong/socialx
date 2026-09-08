<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnreadNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_unread_notification_count(): void
    {
        $recipient =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        for (
            $index = 0;
            $index < 3;
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

        $readNotification =
            new Notification;

        $readNotification->user_id =
            $recipient->id;

        $readNotification->actor_id =
            $actor->id;

        $readNotification->type =
            Notification::TYPE_FOLLOW;

        $readNotification->post_id =
            null;

        $readNotification->read_at =
            now();

        $readNotification->save();

        Sanctum::actingAs(
            $recipient
        );

        $this
            ->getJson(
                '/api/notifications/unread-count'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.unread_count',
                3
            );
    }

    public function test_user_can_mark_notification_as_read(): void
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

        Sanctum::actingAs(
            $recipient
        );

        $response =
            $this->patchJson(
                "/api/notifications/{$notification->id}/read"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.notification.id',
                $notification->id
            )
            ->assertJsonPath(
                'data.unread_count',
                0
            );

        $notification->refresh();

        $this->assertNotNull(
            $notification->read_at
        );
    }

    public function test_mark_as_read_is_idempotent(): void
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

        Sanctum::actingAs(
            $recipient
        );

        $this
            ->patchJson(
                "/api/notifications/{$notification->id}/read"
            )
            ->assertOk();

        $notification->refresh();

        $firstReadAt =
            $notification
                ->read_at
                ?->toISOString();

        $this
            ->patchJson(
                "/api/notifications/{$notification->id}/read"
            )
            ->assertOk();

        $notification->refresh();

        $this->assertSame(
            $firstReadAt,
            $notification
                ->read_at
                ?->toISOString()
        );
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner =
            User::factory()
                ->create();

        $otherUser =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        $notification =
            new Notification;

        $notification->user_id =
            $owner->id;

        $notification->actor_id =
            $actor->id;

        $notification->type =
            Notification::TYPE_FOLLOW;

        $notification->post_id =
            null;

        $notification->save();

        Sanctum::actingAs(
            $otherUser
        );

        $this
            ->patchJson(
                "/api/notifications/{$notification->id}/read"
            )
            ->assertNotFound();

        $notification->refresh();

        $this->assertNull(
            $notification->read_at
        );
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $recipient =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        for (
            $index = 0;
            $index < 3;
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

        $this
            ->patchJson(
                '/api/notifications/read-all'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.unread_count',
                0
            );

        $remaining =
            Notification::query()
                ->where(
                    'user_id',
                    $recipient->id
                )
                ->whereNull(
                    'read_at'
                )
                ->count();

        $this->assertSame(
            0,
            $remaining
        );
    }

    public function test_mark_all_read_does_not_change_another_users_notifications(): void
    {
        $firstUser =
            User::factory()
                ->create();

        $secondUser =
            User::factory()
                ->create();

        $actor =
            User::factory()
                ->create();

        $first =
            new Notification;

        $first->user_id =
            $firstUser->id;

        $first->actor_id =
            $actor->id;

        $first->type =
            Notification::TYPE_FOLLOW;

        $first->post_id =
            null;

        $first->save();

        $second =
            new Notification;

        $second->user_id =
            $secondUser->id;

        $second->actor_id =
            $actor->id;

        $second->type =
            Notification::TYPE_FOLLOW;

        $second->post_id =
            null;

        $second->save();

        Sanctum::actingAs(
            $firstUser
        );

        $this
            ->patchJson(
                '/api/notifications/read-all'
            )
            ->assertOk();

        $first->refresh();
        $second->refresh();

        $this->assertNotNull(
            $first->read_at
        );

        $this->assertNull(
            $second->read_at
        );
    }
}
