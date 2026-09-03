<?php

namespace Tests\Feature;

use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowRequestApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_user_can_get_received_follow_requests(): void
    {
        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $requester =
            User::factory()->create();

        $followRequest =
            $this->createFollowRequest(
                $requester,
                $target
            );

        Sanctum::actingAs(
            $target
        );

        $response =
            $this->getJson(
                '/api/follow-requests'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.requests'
            )
            ->assertJsonPath(
                'data.requests.0.id',
                $followRequest->id
            )
            ->assertJsonPath(
                'data.requests.0.status',
                FollowRequest::STATUS_PENDING
            )
            ->assertJsonPath(
                'data.requests.0.requester.id',
                $requester->id
            )
            ->assertJsonPath(
                'data.requests.0.requester.username',
                $requester->username
            );
    }

    public function test_user_only_sees_requests_sent_to_them(): void
    {
        $viewer =
            User::factory()->create([
                'is_private' => true,
            ]);

        $otherTarget =
            User::factory()->create([
                'is_private' => true,
            ]);

        $requesterOne =
            User::factory()->create();

        $requesterTwo =
            User::factory()->create();

        $myRequest =
            $this->createFollowRequest(
                $requesterOne,
                $viewer
            );

        $this->createFollowRequest(
            $requesterTwo,
            $otherTarget
        );

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/follow-requests'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.requests'
            )
            ->assertJsonPath(
                'data.requests.0.id',
                $myRequest->id
            );
    }

    public function test_follow_requests_are_ordered_newest_first(): void
    {
        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $olderRequester =
            User::factory()->create();

        $newerRequester =
            User::factory()->create();

        $olderRequest =
            $this->createFollowRequest(
                $olderRequester,
                $target
            );

        $newerRequest =
            $this->createFollowRequest(
                $newerRequester,
                $target
            );

        $olderRequest->created_at =
            now()->subHour();

        $olderRequest->updated_at =
            now()->subHour();

        $olderRequest->save();

        $newerRequest->created_at =
            now();

        $newerRequest->updated_at =
            now();

        $newerRequest->save();

        Sanctum::actingAs(
            $target
        );

        $this
            ->getJson(
                '/api/follow-requests'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.requests.0.id',
                $newerRequest->id
            )
            ->assertJsonPath(
                'data.requests.1.id',
                $olderRequest->id
            );
    }

    public function test_inactive_requester_is_hidden(): void
    {
        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        $requester =
            User::factory()->create();

        $this->createFollowRequest(
            $requester,
            $target
        );

        $requester->status =
            'suspended';

        $requester->save();

        Sanctum::actingAs(
            $target
        );

        $this
            ->getJson(
                '/api/follow-requests'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.requests'
            );
    }

    public function test_guest_cannot_get_follow_requests(): void
    {
        $this
            ->getJson(
                '/api/follow-requests'
            )
            ->assertUnauthorized();
    }

    public function test_follow_request_list_can_be_empty(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->getJson(
                '/api/follow-requests'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.requests'
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

    public function test_follow_requests_are_paginated(): void
    {
        $target =
            User::factory()->create([
                'is_private' => true,
            ]);

        for (
            $index = 0;
            $index < 21;
            $index++
        ) {
            $requester =
                User::factory()->create();

            $this->createFollowRequest(
                $requester,
                $target
            );
        }

        Sanctum::actingAs(
            $target
        );

        $this
            ->getJson(
                '/api/follow-requests?page=1'
            )
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.requests'
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
                '/api/follow-requests?page=2'
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.requests'
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                false
            );
    }
}
