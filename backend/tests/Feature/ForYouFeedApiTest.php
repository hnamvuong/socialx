<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ForYouFeedApiTest extends TestCase
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

    public function test_for_you_feed_can_recommend_public_users_not_followed_by_viewer(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create([
                'is_private' => false,
            ]);

        $post =
            Post::factory()
                ->for($author)
                ->create();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/for-you'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $post->id
            );
    }

    public function test_for_you_feed_hides_unfollowed_private_users(): void
    {
        $viewer =
            User::factory()->create();

        $privateAuthor =
            User::factory()->create([
                'is_private' => true,
            ]);

        Post::factory()
            ->for($privateAuthor)
            ->create();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/for-you'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_for_you_feed_can_include_followed_private_user(): void
    {
        $viewer =
            User::factory()->create();

        $privateAuthor =
            User::factory()->create([
                'is_private' => true,
            ]);

        $this->createFollow(
            $viewer,
            $privateAuthor
        );

        $post =
            Post::factory()
                ->for($privateAuthor)
                ->create();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/for-you'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $post->id
            );
    }

    public function test_relationship_boosts_for_you_ranking(): void
    {
        $viewer =
            User::factory()->create();

        $followedAuthor =
            User::factory()->create();

        $otherAuthor =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $followedAuthor
        );

        $timestamp =
            now();

        $otherPost =
            Post::factory()
                ->for($otherAuthor)
                ->create([
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

        $followedPost =
            Post::factory()
                ->for($followedAuthor)
                ->create([
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/feed/for-you'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $followedPost->id
            );

        $ids =
            collect(
                $response->json(
                    'data.posts'
                )
            )
                ->pluck('id');

        $this->assertContains(
            $otherPost->id,
            $ids
        );
    }

    public function test_recency_affects_for_you_ranking(): void
    {
        $viewer =
            User::factory()->create();

        $authorOne =
            User::factory()->create();

        $authorTwo =
            User::factory()->create();

        $olderPost =
            Post::factory()
                ->for($authorOne)
                ->create([
                    'created_at' => now()->subDays(5),
                    'updated_at' => now()->subDays(5),
                ]);

        $newerPost =
            Post::factory()
                ->for($authorTwo)
                ->create([
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/for-you'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $newerPost->id
            );
    }

    public function test_guest_cannot_view_for_you_feed(): void
    {
        $this
            ->getJson(
                '/api/feed/for-you'
            )
            ->assertUnauthorized();
    }
}
