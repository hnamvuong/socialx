<?php

namespace Tests\Feature;

use App\Models\FollowRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_follow_creates_notification(): void
    {
        $viewer =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->postJson(
                "/api/users/{$target->id}/follow"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'following'
            )
            ->assertJsonPath(
                'data.following',
                true
            )
            ->assertJsonPath(
                'data.follow_requested',
                false
            );

        $this->assertDatabaseHas(
            'follows',
            [
                'follower_id' => $viewer->id,

                'following_id' => $target->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $viewer->id,

                'type' => Notification::TYPE_FOLLOW,

                'post_id' => null,

                'read_at' => null,
            ]
        );
    }

    public function test_private_follow_request_does_not_create_follow_notification(): void
    {
        $viewer =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => true,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->postJson(
                "/api/users/{$target->id}/follow"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'requested'
            )
            ->assertJsonPath(
                'data.following',
                false
            )
            ->assertJsonPath(
                'data.follow_requested',
                true
            );

        $this->assertDatabaseHas(
            'follow_requests',
            [
                'requester_id' => $viewer->id,

                'target_id' => $target->id,

                'status' => FollowRequest::STATUS_PENDING,
            ]
        );

        $this->assertDatabaseMissing(
            'follows',
            [
                'follower_id' => $viewer->id,

                'following_id' => $target->id,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $viewer->id,

                'type' => Notification::TYPE_FOLLOW,
            ]
        );
    }

    public function test_accepting_private_follow_request_creates_notification(): void
    {
        $requester =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => true,
                ]);

        Sanctum::actingAs(
            $requester
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $followRequest =
            FollowRequest::query()
                ->where(
                    'requester_id',
                    $requester->id
                )
                ->where(
                    'target_id',
                    $target->id
                )
                ->where(
                    'status',
                    FollowRequest::STATUS_PENDING
                )
                ->firstOrFail();

        /*
         * Người nhận request mới có quyền accept.
         */
        Sanctum::actingAs(
            $target
        );

        $response =
            $this->postJson(
                "/api/follow-requests/{$followRequest->id}/accept"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.accepted',
                true
            )
            ->assertJsonPath(
                'data.requester_id',
                $requester->id
            )
            ->assertJsonPath(
                'data.following',
                true
            );

        $this->assertDatabaseHas(
            'follows',
            [
                'follower_id' => $requester->id,

                'following_id' => $target->id,
            ]
        );

        $this->assertDatabaseMissing(
            'follow_requests',
            [
                'id' => $followRequest->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $requester->id,

                'type' => Notification::TYPE_FOLLOW,

                'post_id' => null,

                'read_at' => null,
            ]
        );
    }

    public function test_rejecting_private_follow_request_does_not_create_notification(): void
    {
        $requester =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => true,
                ]);

        Sanctum::actingAs(
            $requester
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $followRequest =
            FollowRequest::query()
                ->where(
                    'requester_id',
                    $requester->id
                )
                ->where(
                    'target_id',
                    $target->id
                )
                ->firstOrFail();

        Sanctum::actingAs(
            $target
        );

        $response =
            $this->deleteJson(
                "/api/follow-requests/{$followRequest->id}/reject"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.rejected',
                true
            )
            ->assertJsonPath(
                'data.requester_id',
                $requester->id
            );

        $this->assertDatabaseMissing(
            'follow_requests',
            [
                'id' => $followRequest->id,
            ]
        );

        $this->assertDatabaseMissing(
            'follows',
            [
                'follower_id' => $requester->id,

                'following_id' => $target->id,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $requester->id,

                'type' => Notification::TYPE_FOLLOW,
            ]
        );
    }

    public function test_repeated_public_follow_does_not_duplicate_notification(): void
    {
        $viewer =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->assertDatabaseCount(
            'follows',
            1
        );

        $notificationCount =
            Notification::query()
                ->where(
                    'user_id',
                    $target->id
                )
                ->where(
                    'actor_id',
                    $viewer->id
                )
                ->where(
                    'type',
                    Notification::TYPE_FOLLOW
                )
                ->whereNull(
                    'post_id'
                )
                ->count();

        $this->assertSame(
            1,
            $notificationCount
        );
    }

    public function test_unfollow_removes_follow_notification(): void
    {
        $viewer =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $viewer->id,

                'type' => Notification::TYPE_FOLLOW,
            ]
        );

        $response =
            $this->deleteJson(
                "/api/users/{$target->id}/follow"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'none'
            )
            ->assertJsonPath(
                'data.following',
                false
            )
            ->assertJsonPath(
                'data.follow_requested',
                false
            );

        $this->assertDatabaseMissing(
            'follows',
            [
                'follower_id' => $viewer->id,

                'following_id' => $target->id,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $viewer->id,

                'type' => Notification::TYPE_FOLLOW,

                'post_id' => null,
            ]
        );
    }

    public function test_cancelling_private_follow_request_does_not_leave_notification(): void
    {
        $viewer =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => true,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->deleteJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->assertDatabaseMissing(
            'follow_requests',
            [
                'requester_id' => $viewer->id,

                'target_id' => $target->id,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $viewer->id,

                'type' => Notification::TYPE_FOLLOW,
            ]
        );
    }

    public function test_repeated_unfollow_is_idempotent(): void
    {
        $viewer =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        $target =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->deleteJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk();

        $this->deleteJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'none'
            );

        $this->assertDatabaseMissing(
            'follows',
            [
                'follower_id' => $viewer->id,

                'following_id' => $target->id,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $target->id,

                'actor_id' => $viewer->id,

                'type' => Notification::TYPE_FOLLOW,
            ]
        );
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',
                    'is_private' => false,
                ]);

        Sanctum::actingAs(
            $user
        );

        $this->postJson(
            "/api/users/{$user->id}/follow"
        )
            ->assertStatus(
                422
            );

        $this->assertDatabaseCount(
            'follows',
            0
        );

        $this->assertDatabaseCount(
            'notifications',
            0
        );
    }
}
