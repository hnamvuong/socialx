<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowListApiTest extends TestCase
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

    public function test_can_get_user_followers(): void
    {
        $target =
            User::factory()->create();

        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollow(
            $alice,
            $target
        );

        $this->createFollow(
            $bob,
            $target
        );

        $response =
            $this->getJson(
                "/api/users/{$target->username}/followers"
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.users'
            )
            ->assertJsonPath(
                'data.pagination.total',
                2
            );

        $ids =
            collect(
                $response->json(
                    'data.users'
                )
            )
                ->pluck('id')
                ->all();

        $this->assertContains(
            $alice->id,
            $ids
        );

        $this->assertContains(
            $bob->id,
            $ids
        );
    }

    public function test_can_get_users_following_list(): void
    {
        $target =
            User::factory()->create();

        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $this->createFollow(
            $target,
            $alice
        );

        $this->createFollow(
            $target,
            $bob
        );

        $response =
            $this->getJson(
                "/api/users/{$target->username}/following"
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.users'
            )
            ->assertJsonPath(
                'data.pagination.total',
                2
            );

        $ids =
            collect(
                $response->json(
                    'data.users'
                )
            )
                ->pluck('id')
                ->all();

        $this->assertContains(
            $alice->id,
            $ids
        );

        $this->assertContains(
            $bob->id,
            $ids
        );
    }

    public function test_followers_are_ordered_by_follow_time(): void
    {
        $target =
            User::factory()->create();

        $olderFollower =
            User::factory()->create();

        $newerFollower =
            User::factory()->create();

        $olderFollow =
            $this->createFollow(
                $olderFollower,
                $target
            );

        $newerFollow =
            $this->createFollow(
                $newerFollower,
                $target
            );

        $olderFollow->created_at =
            now()->subHour();

        $olderFollow->updated_at =
            now()->subHour();

        $olderFollow->save();

        $newerFollow->created_at =
            now();

        $newerFollow->updated_at =
            now();

        $newerFollow->save();

        $this
            ->getJson(
                "/api/users/{$target->username}/followers"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.users.0.id',
                $newerFollower->id
            )
            ->assertJsonPath(
                'data.users.1.id',
                $olderFollower->id
            );
    }

    public function test_following_is_ordered_by_follow_time(): void
    {
        $target =
            User::factory()->create();

        $olderUser =
            User::factory()->create();

        $newerUser =
            User::factory()->create();

        $olderFollow =
            $this->createFollow(
                $target,
                $olderUser
            );

        $newerFollow =
            $this->createFollow(
                $target,
                $newerUser
            );

        $olderFollow->created_at =
            now()->subHour();

        $olderFollow->updated_at =
            now()->subHour();

        $olderFollow->save();

        $newerFollow->created_at =
            now();

        $newerFollow->updated_at =
            now();

        $newerFollow->save();

        $this
            ->getJson(
                "/api/users/{$target->username}/following"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.users.0.id',
                $newerUser->id
            )
            ->assertJsonPath(
                'data.users.1.id',
                $olderUser->id
            );
    }

    public function test_inactive_users_are_not_returned_in_followers(): void
    {
        $target =
            User::factory()->create();

        $activeFollower =
            User::factory()->create();

        $inactiveFollower =
            User::factory()->create();

        $inactiveFollower->status =
            'suspended';

        $inactiveFollower->save();

        $this->createFollow(
            $activeFollower,
            $target
        );

        $this->createFollow(
            $inactiveFollower,
            $target
        );

        $response =
            $this->getJson(
                "/api/users/{$target->username}/followers"
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.users'
            )
            ->assertJsonPath(
                'data.users.0.id',
                $activeFollower->id
            );
    }

    public function test_inactive_users_are_not_returned_in_following(): void
    {
        $target =
            User::factory()->create();

        $activeUser =
            User::factory()->create();

        $inactiveUser =
            User::factory()->create();

        $inactiveUser->status =
            'suspended';

        $inactiveUser->save();

        $this->createFollow(
            $target,
            $activeUser
        );

        $this->createFollow(
            $target,
            $inactiveUser
        );

        $response =
            $this->getJson(
                "/api/users/{$target->username}/following"
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.users'
            )
            ->assertJsonPath(
                'data.users.0.id',
                $activeUser->id
            );
    }

    public function test_unknown_profile_returns_404_for_followers(): void
    {
        $this
            ->getJson(
                '/api/users/not-found-user/followers'
            )
            ->assertNotFound();
    }

    public function test_unknown_profile_returns_404_for_following(): void
    {
        $this
            ->getJson(
                '/api/users/not-found-user/following'
            )
            ->assertNotFound();
    }

    public function test_followers_list_can_be_empty(): void
    {
        $user =
            User::factory()->create();

        $this
            ->getJson(
                "/api/users/{$user->username}/followers"
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.users'
            )
            ->assertJsonPath(
                'data.pagination.total',
                0
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                false
            );
    }

    public function test_following_list_can_be_empty(): void
    {
        $user =
            User::factory()->create();

        $this
            ->getJson(
                "/api/users/{$user->username}/following"
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.users'
            )
            ->assertJsonPath(
                'data.pagination.total',
                0
            );
    }

    public function test_followers_are_paginated(): void
    {
        $target =
            User::factory()->create();

        for (
            $index = 0;
            $index < 21;
            $index++
        ) {
            $follower =
                User::factory()->create();

            $this->createFollow(
                $follower,
                $target
            );
        }

        $this
            ->getJson(
                "/api/users/{$target->username}/followers?page=1"
            )
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.users'
            )
            ->assertJsonPath(
                'data.pagination.current_page',
                1
            )
            ->assertJsonPath(
                'data.pagination.last_page',
                2
            )
            ->assertJsonPath(
                'data.pagination.total',
                21
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                true
            );

        $this
            ->getJson(
                "/api/users/{$target->username}/followers?page=2"
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.users'
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                false
            );
    }
}
