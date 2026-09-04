<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowingFeedApiTest extends TestCase
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

    private function createLike(
        User $user,
        Post $post
    ): Like {
        $like =
            new Like;

        $like->user_id =
            $user->id;

        $like->post_id =
            $post->id;

        $like->save();

        return $like;
    }

    private function createRepost(
        User $user,
        Post $post
    ): Repost {
        $repost =
            new Repost;

        $repost->user_id =
            $user->id;

        $repost->post_id =
            $post->id;

        $repost->save();

        return $repost;
    }

    private function createBookmark(
        User $user,
        Post $post
    ): Bookmark {
        $bookmark =
            new Bookmark;

        $bookmark->user_id =
            $user->id;

        $bookmark->post_id =
            $post->id;

        $bookmark->save();

        return $bookmark;
    }

    public function test_following_feed_returns_posts_from_followed_users(): void
    {
        $viewer =
            User::factory()->create();

        $followedUser =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $followedUser
        );

        $followedPost =
            Post::factory()
                ->for($followedUser)
                ->create([
                    'content' => 'Followed user post',
                ]);

        $otherPost =
            Post::factory()
                ->for($otherUser)
                ->create([
                    'content' => 'Other user post',
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/feed/following'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts'
            )
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
                ->pluck('id')
                ->all();

        $this->assertNotContains(
            $otherPost->id,
            $ids
        );
    }

    public function test_following_feed_can_include_multiple_followed_users(): void
    {
        $viewer =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $charlie =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $bob
        );

        $this->createFollow(
            $viewer,
            $charlie
        );

        $bobPost =
            Post::factory()
                ->for($bob)
                ->create();

        $charliePost =
            Post::factory()
                ->for($charlie)
                ->create();

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/feed/following'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.posts'
            );

        $ids =
            collect(
                $response->json(
                    'data.posts'
                )
            )
                ->pluck('id')
                ->all();

        $this->assertContains(
            $bobPost->id,
            $ids
        );

        $this->assertContains(
            $charliePost->id,
            $ids
        );
    }

    public function test_following_feed_does_not_return_replies(): void
    {
        $viewer =
            User::factory()->create();

        $followedUser =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $followedUser
        );

        $root =
            Post::factory()
                ->for($followedUser)
                ->create();

        $reply =
            Post::factory()
                ->for($followedUser)
                ->create();

        $reply->parent_post_id =
            $root->id;

        $reply->root_post_id =
            $root->id;

        $reply->save();

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/feed/following'
            );

        $ids =
            collect(
                $response->json(
                    'data.posts'
                )
            )
                ->pluck('id')
                ->all();

        $this->assertContains(
            $root->id,
            $ids
        );

        $this->assertNotContains(
            $reply->id,
            $ids
        );
    }

    public function test_following_feed_orders_posts_newest_first(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        $olderPost =
            Post::factory()
                ->for($author)
                ->create();

        $newerPost =
            Post::factory()
                ->for($author)
                ->create();

        $olderPost->created_at =
            now()->subHour();

        $olderPost->updated_at =
            now()->subHour();

        $olderPost->save();

        $newerPost->created_at =
            now();

        $newerPost->updated_at =
            now();

        $newerPost->save();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/following'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $newerPost->id
            )
            ->assertJsonPath(
                'data.posts.1.id',
                $olderPost->id
            );
    }

    public function test_following_feed_hides_posts_from_inactive_authors(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        Post::factory()
            ->for($author)
            ->create();

        $author->status =
            'suspended';

        $author->save();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/following'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_following_feed_returns_viewer_interaction_state(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $this->createLike(
            $viewer,
            $post
        );

        $this->createRepost(
            $viewer,
            $post
        );

        $this->createBookmark(
            $viewer,
            $post
        );

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/following'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.liked_by_me',
                true
            )
            ->assertJsonPath(
                'data.posts.0.reposted_by_me',
                true
            )
            ->assertJsonPath(
                'data.posts.0.bookmarked_by_me',
                true
            );
    }

    public function test_following_feed_can_be_empty(): void
    {
        $viewer =
            User::factory()->create();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/following'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.next_cursor',
                null
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                false
            );
    }

    public function test_guest_cannot_view_following_feed(): void
    {
        $this
            ->getJson(
                '/api/feed/following'
            )
            ->assertUnauthorized();
    }

    public function test_following_feed_uses_cursor_pagination(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        Post::factory()
            ->count(21)
            ->for($author)
            ->create();

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/feed/following'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.per_page',
                20
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                true
            );

        $nextCursor =
            $response->json(
                'data.pagination.next_cursor'
            );

        $this->assertIsString(
            $nextCursor
        );

        $this->assertNotSame(
            '',
            $nextCursor
        );
    }

    public function test_following_feed_can_load_next_cursor(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        Post::factory()
            ->count(21)
            ->for($author)
            ->create();

        Sanctum::actingAs(
            $viewer
        );

        $firstResponse =
            $this->getJson(
                '/api/feed/following'
            );

        $firstResponse
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.posts'
            );

        $cursor =
            $firstResponse->json(
                'data.pagination.next_cursor'
            );

        $secondResponse =
            $this->getJson(
                '/api/feed/following?cursor='
                .urlencode($cursor)
            );

        $secondResponse
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                false
            )
            ->assertJsonPath(
                'data.pagination.next_cursor',
                null
            );
    }

    public function test_cursor_pages_do_not_duplicate_posts(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        Post::factory()
            ->count(25)
            ->for($author)
            ->create();

        Sanctum::actingAs(
            $viewer
        );

        $firstResponse =
            $this->getJson(
                '/api/feed/following'
            );

        $cursor =
            $firstResponse->json(
                'data.pagination.next_cursor'
            );

        $secondResponse =
            $this->getJson(
                '/api/feed/following?cursor='
                .urlencode($cursor)
            );

        $firstIds =
            collect(
                $firstResponse->json(
                    'data.posts'
                )
            )
                ->pluck('id');

        $secondIds =
            collect(
                $secondResponse->json(
                    'data.posts'
                )
            )
                ->pluck('id');

        $duplicates =
            $firstIds
                ->intersect(
                    $secondIds
                );

        $this->assertCount(
            0,
            $duplicates
        );
    }

    public function test_cursor_order_is_stable_when_posts_have_same_timestamp(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $this->createFollow(
            $viewer,
            $author
        );

        $timestamp =
            now();

        $olderIdPost =
            Post::factory()
                ->for($author)
                ->create([
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

        $newerIdPost =
            Post::factory()
                ->for($author)
                ->create([
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                '/api/feed/following'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $newerIdPost->id
            )
            ->assertJsonPath(
                'data.posts.1.id',
                $olderIdPost->id
            );
    }
}
