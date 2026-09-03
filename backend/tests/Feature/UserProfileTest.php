<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_can_be_viewed(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'bio' => 'Hello SocialX',
            'location' => 'Tokyo',
            'website' => 'https://example.com',
        ]);

        $response = $this->getJson(
            '/api/users/alice'
        );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => 'alice',
                        'display_name' => 'Alice',
                        'bio' => 'Hello SocialX',
                        'location' => 'Tokyo',
                        'website' => 'https://example.com',
                    ],
                ],
            ]);
    }

    public function test_public_profile_does_not_expose_email(): void
    {
        User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $this
            ->getJson('/api/users/alice')
            ->assertOk()
            ->assertJsonMissing([
                'email' => 'alice@example.com',
            ]);
    }

    public function test_username_lookup_is_case_insensitive(): void
    {
        User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $this
            ->getJson('/api/users/ALICE')
            ->assertOk()
            ->assertJsonPath(
                'data.user.username',
                'alice'
            );
    }

    public function test_unknown_profile_returns_404(): void
    {
        $this
            ->getJson(
                '/api/users/not-found'
            )
            ->assertNotFound();
    }

    public function test_inactive_profile_is_not_publicly_visible(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $user->status = 'suspended';
        $user->save();

        $this
            ->getJson('/api/users/alice')
            ->assertNotFound();
    }

    public function test_guest_profile_returns_no_follow_relationship(): void
    {
        $target =
            User::factory()->create();

        $this
            ->getJson(
                "/api/users/{$target->username}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.user.relationship',
                'none'
            )
            ->assertJsonPath(
                'data.user.following',
                false
            )
            ->assertJsonPath(
                'data.user.follow_requested',
                false
            );
    }

    public function test_profile_returns_self_relationship(): void
    {
        $user =
            User::factory()->create();

        $token =
            $user
                ->createToken(
                    'test-token'
                )
                ->plainTextToken;

        $this
            ->withToken(
                $token
            )
            ->getJson(
                "/api/users/{$user->username}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.user.relationship',
                'self'
            )
            ->assertJsonPath(
                'data.user.following',
                false
            )
            ->assertJsonPath(
                'data.user.follow_requested',
                false
            );
    }

    public function test_profile_detects_following_from_bearer_token(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create();

        $follow =
            new Follow;

        $follow->follower_id =
            $viewer->id;

        $follow->following_id =
            $target->id;

        $follow->save();

        $token =
            $viewer
                ->createToken(
                    'test-token'
                )
                ->plainTextToken;

        $this
            ->withToken(
                $token
            )
            ->getJson(
                "/api/users/{$target->username}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.user.relationship',
                'following'
            )
            ->assertJsonPath(
                'data.user.following',
                true
            )
            ->assertJsonPath(
                'data.user.follow_requested',
                false
            );
    }

    public function test_profile_detects_follow_request_from_bearer_token(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $followRequest =
            new FollowRequest;

        $followRequest->requester_id =
            $viewer->id;

        $followRequest->target_id =
            $target->id;

        $followRequest->status =
            FollowRequest::STATUS_PENDING;

        $followRequest->save();

        $token =
            $viewer
                ->createToken(
                    'test-token'
                )
                ->plainTextToken;

        $this
            ->withToken(
                $token
            )
            ->getJson(
                "/api/users/{$target->username}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.user.relationship',
                'requested'
            )
            ->assertJsonPath(
                'data.user.following',
                false
            )
            ->assertJsonPath(
                'data.user.follow_requested',
                true
            );
    }

    public function test_profile_returns_follow_counts(): void
    {
        $target =
            User::factory()->create();

        $followerOne =
            User::factory()->create();

        $followerTwo =
            User::factory()->create();

        $followingUser =
            User::factory()->create();

        foreach (
            [
                $followerOne,
                $followerTwo,
            ] as $follower
        ) {
            $follow =
                new Follow;

            $follow->follower_id =
                $follower->id;

            $follow->following_id =
                $target->id;

            $follow->save();
        }

        $follow =
            new Follow;

        $follow->follower_id =
            $target->id;

        $follow->following_id =
            $followingUser->id;

        $follow->save();

        $this
            ->getJson(
                "/api/users/{$target->username}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.user.followers_count',
                2
            )
            ->assertJsonPath(
                'data.user.following_count',
                1
            );
    }
}
