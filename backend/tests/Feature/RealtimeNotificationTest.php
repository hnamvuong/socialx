<?php

namespace Tests\Feature;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_notification_dispatches_realtime_event(): void
    {
        Event::fake([
            NotificationCreated::class,
        ]);

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

        Event::assertDispatched(
            NotificationCreated::class,
            function (
                NotificationCreated $event
            ) use (
                $notification
            ): bool {
                return $event
                    ->notification
                    ->is(
                        $notification
                    );
            }
        );
    }
}
