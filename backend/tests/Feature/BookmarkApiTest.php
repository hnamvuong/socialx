<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookmarkApiTest extends TestCase
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

    public function test_authenticated_user_can_bookmark_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.bookmarked',
                true
            );

        $this->assertDatabaseHas(
            'bookmarks',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_bookmark_is_idempotent(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertOk();

        $this
            ->postJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.bookmarked',
                true
            );

        $this->assertDatabaseCount(
            'bookmarks',
            1
        );
    }

    public function test_user_can_unbookmark_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createBookmark(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.bookmarked',
                false
            );

        $this->assertDatabaseMissing(
            'bookmarks',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_unbookmark_is_idempotent(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.bookmarked',
                false
            );
    }

    public function test_guest_cannot_bookmark_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->postJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertUnauthorized();
    }

    public function test_guest_cannot_unbookmark_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/bookmark"
            )
            ->assertUnauthorized();
    }

    public function test_bookmark_unknown_post_returns_404(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                '/api/posts/999999/bookmark'
            )
            ->assertNotFound();
    }

    public function test_reply_can_be_bookmarked(): void
    {
        $user =
            User::factory()->create();

        $root =
            Post::factory()->create();

        $reply =
            Post::factory()->create();

        $reply->parent_post_id =
            $root->id;

        $reply->root_post_id =
            $root->id;

        $reply->save();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                "/api/posts/{$reply->id}/bookmark"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.bookmarked',
                true
            );
    }

    public function test_post_detail_detects_bookmark_from_bearer_token(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createBookmark(
            $user,
            $post
        );

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
                "/api/posts/{$post->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.bookmarked_by_me',
                true
            );
    }

    public function test_guest_post_detail_returns_not_bookmarked(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.bookmarked_by_me',
                false
            );
    }

    public function test_thread_returns_bookmark_state_from_bearer_token(): void
    {
        $user =
            User::factory()->create();

        $root =
            Post::factory()->create();

        $reply =
            Post::factory()->create();

        $reply->parent_post_id =
            $root->id;

        $reply->root_post_id =
            $root->id;

        $reply->save();

        $this->createBookmark(
            $user,
            $reply
        );

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
                "/api/posts/{$root->id}/thread"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.root.bookmarked_by_me',
                false
            )
            ->assertJsonPath(
                'data.replies.0.bookmarked_by_me',
                true
            );
    }
}
