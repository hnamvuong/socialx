<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowDatabaseTest extends TestCase
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

    public function test_user_can_follow_another_user(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $follow =
            $this->createFollow(
                $alice,
                $bob
            );

        $this->assertTrue(
            $follow
                ->follower
                ->is($alice)
        );

        $this->assertTrue(
            $follow
                ->following
                ->is($bob)
        );

        $this->assertDatabaseHas(
            'follows',
            [
                'follower_id' => $alice->id,

                'following_id' => $bob->id,
            ]
        );
    }

    public function test_same_follow_relationship_cannot_exist_twice(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollow(
            $alice,
            $bob
        );

        $this->expectException(
            QueryException::class
        );

        $this->createFollow(
            $alice,
            $bob
        );
    }

    public function test_user_can_follow_multiple_users(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $charlie =
            User::factory()->create();

        $this->createFollow(
            $alice,
            $bob
        );

        $this->createFollow(
            $alice,
            $charlie
        );

        $this->assertSame(
            2,
            $alice
                ->following()
                ->count()
        );
    }

    public function test_user_can_have_multiple_followers(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $charlie =
            User::factory()->create();

        $this->createFollow(
            $alice,
            $charlie
        );

        $this->createFollow(
            $bob,
            $charlie
        );

        $this->assertSame(
            2,
            $charlie
                ->followers()
                ->count()
        );
    }

    public function test_following_relationship_returns_correct_users(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $charlie =
            User::factory()->create();

        $this->createFollow(
            $alice,
            $bob
        );

        $followingIds =
            $alice
                ->following()
                ->pluck(
                    'users.id'
                )
                ->all();

        $this->assertContains(
            $bob->id,
            $followingIds
        );

        $this->assertNotContains(
            $charlie->id,
            $followingIds
        );
    }

    public function test_followers_relationship_returns_correct_users(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollow(
            $alice,
            $bob
        );

        $followerIds =
            $bob
                ->followers()
                ->pluck(
                    'users.id'
                )
                ->all();

        $this->assertContains(
            $alice->id,
            $followerIds
        );
    }

    public function test_follow_relationship_is_deleted_when_follower_is_deleted(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $follow =
            $this->createFollow(
                $alice,
                $bob
            );

        $alice->delete();

        $this->assertDatabaseMissing(
            'follows',
            [
                'id' => $follow->id,
            ]
        );
    }

    public function test_follow_relationship_is_deleted_when_following_user_is_deleted(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $follow =
            $this->createFollow(
                $alice,
                $bob
            );

        $bob->delete();

        $this->assertDatabaseMissing(
            'follows',
            [
                'id' => $follow->id,
            ]
        );
    }

    public function test_user_can_send_follow_request(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $request =
            $this->createFollowRequest(
                $alice,
                $bob
            );

        $this->assertTrue(
            $request
                ->requester
                ->is($alice)
        );

        $this->assertTrue(
            $request
                ->target
                ->is($bob)
        );

        $this->assertSame(
            FollowRequest::STATUS_PENDING,
            $request->status
        );
    }

    public function test_same_follow_request_cannot_exist_twice(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollowRequest(
            $alice,
            $bob
        );

        $this->expectException(
            QueryException::class
        );

        $this->createFollowRequest(
            $alice,
            $bob
        );
    }

    public function test_sent_follow_requests_relationship_works(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollowRequest(
            $alice,
            $bob
        );

        $this->assertSame(
            1,
            $alice
                ->sentFollowRequests()
                ->count()
        );
    }

    public function test_received_follow_requests_relationship_works(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollowRequest(
            $alice,
            $bob
        );

        $this->assertSame(
            1,
            $bob
                ->receivedFollowRequests()
                ->count()
        );
    }

    public function test_follow_request_is_deleted_when_requester_is_deleted(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $request =
            $this->createFollowRequest(
                $alice,
                $bob
            );

        $alice->delete();

        $this->assertDatabaseMissing(
            'follow_requests',
            [
                'id' => $request->id,
            ]
        );
    }

    public function test_follow_request_is_deleted_when_target_is_deleted(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $request =
            $this->createFollowRequest(
                $alice,
                $bob
            );

        $bob->delete();

        $this->assertDatabaseMissing(
            'follow_requests',
            [
                'id' => $request->id,
            ]
        );
    }
}
