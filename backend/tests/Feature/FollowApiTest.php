<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowApiTest extends TestCase
{
    use RefreshDatabase;

    private function createFollow(
        User $follower,
        User $following
    ): Follow {
        $follow =
            new Follow;

        $follow->follower_id =
            $follower->id;

        $follow->following_id =
            $following->id;

        $follow->save();

        return $follow;
    }

    private function createFollowRequest(
        User $requester,
        User $target
    ): FollowRequest {
        $followRequest =
            new FollowRequest;

        $followRequest->requester_id =
            $requester->id;

        $followRequest->target_id =
            $target->id;

        $followRequest->status =
            FollowRequest::STATUS_PENDING;

        $followRequest->save();

        return $followRequest;
    }

    public function test_user_can_follow_public_account(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => false,
            ]);

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->postJson(
                "/api/users/{$target->id}/follow"
            )
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
    }

    public function test_follow_public_account_is_idempotent(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => false,
            ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )->assertOk();

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'following'
            );

        $this->assertDatabaseCount(
            'follows',
            1
        );
    }

    public function test_follow_private_account_creates_request(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->postJson(
                "/api/users/{$target->id}/follow"
            )
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
    }

    public function test_private_follow_request_is_idempotent(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )->assertOk();

        $this->postJson(
            "/api/users/{$target->id}/follow"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'requested'
            );

        $this->assertDatabaseCount(
            'follow_requests',
            1
        );
    }

    public function test_user_can_unfollow(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $target
        );

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->deleteJson(
                "/api/users/{$target->id}/follow"
            )
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
    }

    public function test_user_can_cancel_follow_request(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $this->createFollowRequest(
            $viewer,
            $target
        );

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->deleteJson(
                "/api/users/{$target->id}/follow"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'none'
            )
            ->assertJsonPath(
                'data.follow_requested',
                false
            );

        $this->assertDatabaseMissing(
            'follow_requests',
            [
                'requester_id' => $viewer->id,

                'target_id' => $target->id,
            ]
        );
    }

    public function test_unfollow_is_idempotent(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->deleteJson(
                "/api/users/{$target->id}/follow"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.relationship',
                'none'
            );
    }

    public function test_user_cannot_follow_self(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                "/api/users/{$user->id}/follow"
            )
            ->assertUnprocessable();

        $this->assertDatabaseCount(
            'follows',
            0
        );

        $this->assertDatabaseCount(
            'follow_requests',
            0
        );
    }

    public function test_guest_cannot_follow_user(): void
    {
        $target =
            User::factory()->create();

        $this
            ->postJson(
                "/api/users/{$target->id}/follow"
            )
            ->assertUnauthorized();
    }

    public function test_guest_cannot_unfollow_user(): void
    {
        $target =
            User::factory()->create();

        $this
            ->deleteJson(
                "/api/users/{$target->id}/follow"
            )
            ->assertUnauthorized();
    }

    public function test_cannot_follow_inactive_user(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create();

        $target->status =
            'suspended';

        $target->save();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->postJson(
                "/api/users/{$target->id}/follow"
            )
            ->assertNotFound();
    }

    public function test_target_can_accept_follow_request(): void
    {
        $requester =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $followRequest =
            $this->createFollowRequest(
                $requester,
                $target
            );

        Sanctum::actingAs(
            $target
        );

        $this
            ->postJson(
                "/api/follow-requests/{$followRequest->id}/accept"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.accepted',
                true
            )
            ->assertJsonPath(
                'data.requester_id',
                $requester->id
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
    }

    public function test_target_can_reject_follow_request(): void
    {
        $requester =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $followRequest =
            $this->createFollowRequest(
                $requester,
                $target
            );

        Sanctum::actingAs(
            $target
        );

        $this
            ->deleteJson(
                "/api/follow-requests/{$followRequest->id}/reject"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.rejected',
                true
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
    }

    public function test_other_user_cannot_accept_follow_request(): void
    {
        $requester =
            User::factory()->create();

        $target =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $followRequest =
            $this->createFollowRequest(
                $requester,
                $target
            );

        Sanctum::actingAs(
            $otherUser
        );

        $this
            ->postJson(
                "/api/follow-requests/{$followRequest->id}/accept"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'follow_requests',
            [
                'id' => $followRequest->id,
            ]
        );
    }

    public function test_other_user_cannot_reject_follow_request(): void
    {
        $requester =
            User::factory()->create();

        $target =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $followRequest =
            $this->createFollowRequest(
                $requester,
                $target
            );

        Sanctum::actingAs(
            $otherUser
        );

        $this
            ->deleteJson(
                "/api/follow-requests/{$followRequest->id}/reject"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'follow_requests',
            [
                'id' => $followRequest->id,
            ]
        );
    }
}
