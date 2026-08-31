<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookmarkPageTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_bookmark_page_only_returns_current_users_bookmarks(): void
    {
        $user =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $myPost =
            Post::factory()->create([
                'content' => 'My bookmarked post',
            ]);

        $otherPost =
            Post::factory()->create([
                'content' => 'Other user bookmarked post',
            ]);

        $this->createBookmark(
            $user,
            $myPost
        );

        $this->createBookmark(
            $otherUser,
            $otherPost
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts'
            )
            ->assertJsonPath(
                'data.posts.0.id',
                $myPost->id
            )
            ->assertJsonPath(
                'data.posts.0.bookmarked_by_me',
                true
            );

        $returnedPostIds =
            collect(
                $response->json(
                    'data.posts'
                )
            )
                ->pluck('id')
                ->all();

        $this->assertContains(
            $myPost->id,
            $returnedPostIds
        );

        $this->assertNotContains(
            $otherPost->id,
            $returnedPostIds
        );
    }

    public function test_bookmarks_are_ordered_by_bookmark_time(): void
    {
        $user =
            User::factory()->create();

        $olderPost =
            Post::factory()->create([
                'content' => 'Older bookmark',
            ]);

        $newerPost =
            Post::factory()->create([
                'content' => 'Newer bookmark',
            ]);

        $olderBookmark =
            $this->createBookmark(
                $user,
                $olderPost
            );

        $newerBookmark =
            $this->createBookmark(
                $user,
                $newerPost
            );

        $olderBookmark->created_at =
            now()->subHour();

        $olderBookmark->updated_at =
            now()->subHour();

        $olderBookmark->save();

        $newerBookmark->created_at =
            now();

        $newerBookmark->updated_at =
            now();

        $newerBookmark->save();

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.posts'
            )
            ->assertJsonPath(
                'data.posts.0.id',
                $newerPost->id
            )
            ->assertJsonPath(
                'data.posts.1.id',
                $olderPost->id
            );
    }

    public function test_guest_cannot_view_bookmark_page(): void
    {
        $this
            ->getJson(
                '/api/bookmarks'
            )
            ->assertUnauthorized();
    }

    public function test_bookmark_page_can_be_empty(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.current_page',
                1
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

    public function test_bookmarks_from_inactive_authors_are_hidden(): void
    {
        $user =
            User::factory()->create();

        $author =
            User::factory()->create();

        $author->status =
            'suspended';

        $author->save();

        $post =
            Post::factory()
                ->for($author)
                ->create([
                    'content' => 'Hidden post',
                ]);

        $this->createBookmark(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.total',
                0
            );
    }

    public function test_bookmark_page_returns_like_and_repost_state(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create([
                'content' => 'Bookmarked and engaged',
            ]);

        $this->createBookmark(
            $user,
            $post
        );

        $this->createLike(
            $user,
            $post
        );

        $this->createRepost(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $post->id
            )
            ->assertJsonPath(
                'data.posts.0.likes_count',
                1
            )
            ->assertJsonPath(
                'data.posts.0.liked_by_me',
                true
            )
            ->assertJsonPath(
                'data.posts.0.reposts_count',
                1
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

    public function test_bookmark_page_returns_false_like_and_repost_state_when_user_has_not_interacted(): void
    {
        $user =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create([
                'content' => 'Only bookmarked by viewer',
            ]);

        $this->createBookmark(
            $user,
            $post
        );

        $this->createLike(
            $otherUser,
            $post
        );

        $this->createRepost(
            $otherUser,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.likes_count',
                1
            )
            ->assertJsonPath(
                'data.posts.0.liked_by_me',
                false
            )
            ->assertJsonPath(
                'data.posts.0.reposts_count',
                1
            )
            ->assertJsonPath(
                'data.posts.0.reposted_by_me',
                false
            )
            ->assertJsonPath(
                'data.posts.0.bookmarked_by_me',
                true
            );
    }

    public function test_bookmark_page_returns_post_media(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create([
                'content' => 'Post with media',
            ]);

        $post
            ->media()
            ->create([
                'type' => 'image',

                'path' => 'posts/test/image.jpg',

                'mime_type' => 'image/jpeg',

                'width' => 1200,

                'height' => 800,

                'sort_order' => 0,
            ]);

        $this->createBookmark(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts.0.media'
            )
            ->assertJsonPath(
                'data.posts.0.media.0.type',
                'image'
            )
            ->assertJsonPath(
                'data.posts.0.media.0.width',
                1200
            )
            ->assertJsonPath(
                'data.posts.0.media.0.height',
                800
            );
    }

    public function test_bookmark_page_returns_quoted_post_data(): void
    {
        $user =
            User::factory()->create();

        $original =
            Post::factory()->create([
                'content' => 'Original post',
            ]);

        $quote =
            Post::factory()->create([
                'content' => 'My quote',
            ]);

        $quote->quoted_post_id =
            $original->id;

        $quote->save();

        $this->createBookmark(
            $user,
            $quote
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                '/api/bookmarks'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $quote->id
            )
            ->assertJsonPath(
                'data.posts.0.quoted_post_id',
                $original->id
            )
            ->assertJsonPath(
                'data.posts.0.quoted_post.id',
                $original->id
            )
            ->assertJsonPath(
                'data.posts.0.quoted_post.content',
                'Original post'
            );
    }

    public function test_bookmark_page_returns_pagination_metadata(): void
    {
        $user =
            User::factory()->create();

        for ($index = 1; $index <= 21; $index++) {
            $post =
                Post::factory()->create([
                    'content' => "Bookmark {$index}",
                ]);

            $bookmark =
                $this->createBookmark(
                    $user,
                    $post
                );

            $bookmark->created_at =
                now()->subSeconds(
                    21 - $index
                );

            $bookmark->updated_at =
                $bookmark->created_at;

            $bookmark->save();
        }

        Sanctum::actingAs(
            $user
        );

        $firstPage =
            $this->getJson(
                '/api/bookmarks?page=1'
            );

        $firstPage
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.posts'
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
                'data.pagination.per_page',
                20
            )
            ->assertJsonPath(
                'data.pagination.total',
                21
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                true
            );

        $secondPage =
            $this->getJson(
                '/api/bookmarks?page=2'
            );

        $secondPage
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.current_page',
                2
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
                false
            );
    }

    public function test_bookmark_page_uses_bookmark_time_not_post_creation_time(): void
    {
        $user =
            User::factory()->create();

        $oldPost =
            Post::factory()->create([
                'content' => 'Old post bookmarked recently',
            ]);

        $oldPost->created_at =
            now()->subYear();

        $oldPost->updated_at =
            now()->subYear();

        $oldPost->save();

        $newPost =
            Post::factory()->create([
                'content' => 'New post bookmarked earlier',
            ]);

        $recentBookmark =
            $this->createBookmark(
                $user,
                $oldPost
            );

        $earlierBookmark =
            $this->createBookmark(
                $user,
                $newPost
            );

        $recentBookmark->created_at =
            now();

        $recentBookmark->updated_at =
            now();

        $recentBookmark->save();

        $earlierBookmark->created_at =
            now()->subDay();

        $earlierBookmark->updated_at =
            now()->subDay();

        $earlierBookmark->save();

        Sanctum::actingAs(
            $user
        );

        $this
            ->getJson(
                '/api/bookmarks'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $oldPost->id
            )
            ->assertJsonPath(
                'data.posts.1.id',
                $newPost->id
            );
    }
}
