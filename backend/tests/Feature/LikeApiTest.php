<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeApiTest extends TestCase
{
    use RefreshDatabase;

    private function createLike(
        User $user,
        Post $post
    ): Like {
        $like = new Like;

        $like->user_id =
            $user->id;

        $like->post_id =
            $post->id;

        $like->save();

        return $like;
    }

    public function test_authenticated_user_can_like_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->postJson(
                "/api/posts/{$post->id}/like"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            )
            ->assertJsonPath(
                'data.likes_count',
                1
            );

        $this->assertDatabaseHas(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_like_is_idempotent(): void
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
                "/api/posts/{$post->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            )
            ->assertJsonPath(
                'data.likes_count',
                1
            );

        $this
            ->postJson(
                "/api/posts/{$post->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            )
            ->assertJsonPath(
                'data.likes_count',
                1
            );

        $this->assertDatabaseCount(
            'likes',
            1
        );

        $this->assertDatabaseHas(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_user_can_unlike_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->deleteJson(
                "/api/posts/{$post->id}/like"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                false
            )
            ->assertJsonPath(
                'data.likes_count',
                0
            );

        $this->assertDatabaseMissing(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_unlike_is_idempotent(): void
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
                "/api/posts/{$post->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                false
            )
            ->assertJsonPath(
                'data.likes_count',
                0
            );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                false
            )
            ->assertJsonPath(
                'data.likes_count',
                0
            );

        $this->assertDatabaseMissing(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_like_count_includes_other_users(): void
    {
        $firstUser =
            User::factory()->create();

        $secondUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $firstUser,
            $post
        );

        Sanctum::actingAs(
            $secondUser
        );

        $this
            ->postJson(
                "/api/posts/{$post->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            )
            ->assertJsonPath(
                'data.likes_count',
                2
            );

        $this->assertDatabaseCount(
            'likes',
            2
        );
    }

    public function test_guest_cannot_like_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->postJson(
                "/api/posts/{$post->id}/like"
            )
            ->assertUnauthorized();

        $this->assertDatabaseCount(
            'likes',
            0
        );
    }

    public function test_guest_cannot_unlike_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $user,
            $post
        );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/like"
            )
            ->assertUnauthorized();

        $this->assertDatabaseHas(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_like_unknown_post_returns_404(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                '/api/posts/999999/like'
            )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'likes',
            0
        );
    }

    public function test_unlike_unknown_post_returns_404(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->deleteJson(
                '/api/posts/999999/like'
            )
            ->assertNotFound();
    }

    public function test_post_detail_returns_like_state_for_guest(): void
    {
        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $otherUser,
            $post
        );

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.likes_count',
                1
            )
            ->assertJsonPath(
                'data.post.liked_by_me',
                false
            );
    }

    public function test_post_detail_returns_liked_by_me_for_authenticated_user(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.likes_count',
                1
            )
            ->assertJsonPath(
                'data.post.liked_by_me',
                true
            );
    }

    public function test_post_detail_returns_not_liked_by_me_when_user_has_not_liked_post(): void
    {
        $viewer =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $otherUser,
            $post
        );

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.likes_count',
                1
            )
            ->assertJsonPath(
                'data.post.liked_by_me',
                false
            );
    }

    public function test_thread_returns_like_state_for_each_post(): void
    {
        $user =
            User::factory()->create();

        $root =
            Post::factory()->create([
                'content' => 'Root post',
            ]);

        $reply =
            Post::factory()->create([
                'content' => 'Reply post',
            ]);

        $reply->parent_post_id =
            $root->id;

        $reply->root_post_id =
            $root->id;

        $reply->save();

        $this->createLike(
            $user,
            $reply
        );

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->getJson(
                "/api/posts/{$root->id}/thread"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.root.id',
                $root->id
            )
            ->assertJsonPath(
                'data.root.likes_count',
                0
            )
            ->assertJsonPath(
                'data.root.liked_by_me',
                false
            )
            ->assertJsonPath(
                'data.replies.0.id',
                $reply->id
            )
            ->assertJsonPath(
                'data.replies.0.likes_count',
                1
            )
            ->assertJsonPath(
                'data.replies.0.liked_by_me',
                true
            );
    }

    public function test_thread_like_counts_include_multiple_users(): void
    {
        $viewer =
            User::factory()->create();

        $otherUser =
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

        $this->createLike(
            $viewer,
            $root
        );

        $this->createLike(
            $otherUser,
            $root
        );

        $this->createLike(
            $otherUser,
            $reply
        );

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->getJson(
                "/api/posts/{$root->id}/thread"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.root.likes_count',
                2
            )
            ->assertJsonPath(
                'data.root.liked_by_me',
                true
            )
            ->assertJsonPath(
                'data.replies.0.likes_count',
                1
            )
            ->assertJsonPath(
                'data.replies.0.liked_by_me',
                false
            );
    }

    public function test_reply_can_be_liked_like_normal_post(): void
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
                "/api/posts/{$reply->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            )
            ->assertJsonPath(
                'data.likes_count',
                1
            );

        $this->assertDatabaseHas(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $reply->id,
            ]
        );
    }

    public function test_unlike_only_removes_current_users_like(): void
    {
        $currentUser =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createLike(
            $currentUser,
            $post
        );

        $this->createLike(
            $otherUser,
            $post
        );

        Sanctum::actingAs(
            $currentUser
        );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/like"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                false
            )
            ->assertJsonPath(
                'data.likes_count',
                1
            );

        $this->assertDatabaseMissing(
            'likes',
            [
                'user_id' => $currentUser->id,

                'post_id' => $post->id,
            ]
        );

        $this->assertDatabaseHas(
            'likes',
            [
                'user_id' => $otherUser->id,

                'post_id' => $post->id,
            ]
        );
    }
}
