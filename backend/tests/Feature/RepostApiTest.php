<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RepostApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_authenticated_user_can_repost_post(): void
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
                "/api/posts/{$post->id}/repost"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.reposted',
                true
            )
            ->assertJsonPath(
                'data.reposts_count',
                1
            );

        $this->assertDatabaseHas(
            'reposts',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_repost_is_idempotent(): void
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
                "/api/posts/{$post->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposts_count',
                1
            );

        $this
            ->postJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposted',
                true
            )
            ->assertJsonPath(
                'data.reposts_count',
                1
            );

        $this->assertDatabaseCount(
            'reposts',
            1
        );
    }

    public function test_user_can_unrepost_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createRepost(
            $user,
            $post
        );

        Sanctum::actingAs(
            $user
        );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposted',
                false
            )
            ->assertJsonPath(
                'data.reposts_count',
                0
            );

        $this->assertDatabaseMissing(
            'reposts',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_unrepost_is_idempotent(): void
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
                "/api/posts/{$post->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposted',
                false
            )
            ->assertJsonPath(
                'data.reposts_count',
                0
            );
    }

    public function test_repost_count_includes_other_users(): void
    {
        $firstUser =
            User::factory()->create();

        $secondUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createRepost(
            $firstUser,
            $post
        );

        Sanctum::actingAs(
            $secondUser
        );

        $this
            ->postJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposts_count',
                2
            );
    }

    public function test_unrepost_only_removes_current_users_repost(): void
    {
        $currentUser =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createRepost(
            $currentUser,
            $post
        );

        $this->createRepost(
            $otherUser,
            $post
        );

        Sanctum::actingAs(
            $currentUser
        );

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposts_count',
                1
            );

        $this->assertDatabaseMissing(
            'reposts',
            [
                'user_id' => $currentUser->id,

                'post_id' => $post->id,
            ]
        );

        $this->assertDatabaseHas(
            'reposts',
            [
                'user_id' => $otherUser->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_guest_cannot_repost_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->postJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertUnauthorized();
    }

    public function test_guest_cannot_unrepost_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->deleteJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertUnauthorized();
    }

    public function test_repost_unknown_post_returns_404(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs(
            $user
        );

        $this
            ->postJson(
                '/api/posts/999999/repost'
            )
            ->assertNotFound();
    }

    public function test_cannot_repost_post_of_inactive_user(): void
    {
        $viewer =
            User::factory()->create();

        $author =
            User::factory()->create();

        $author->status =
            'suspended';

        $author->save();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        Sanctum::actingAs(
            $viewer
        );

        $this
            ->postJson(
                "/api/posts/{$post->id}/repost"
            )
            ->assertNotFound();
    }

    public function test_post_detail_returns_repost_state_for_guest(): void
    {
        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createRepost(
            $otherUser,
            $post
        );

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.reposts_count',
                1
            )
            ->assertJsonPath(
                'data.post.reposted_by_me',
                false
            );
    }

    public function test_post_detail_detects_reposted_by_me_from_bearer_token(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $this->createRepost(
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
                'data.post.reposts_count',
                1
            )
            ->assertJsonPath(
                'data.post.reposted_by_me',
                true
            );
    }

    public function test_thread_returns_repost_state_for_each_post(): void
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

        $this->createRepost(
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
                'data.root.reposts_count',
                0
            )
            ->assertJsonPath(
                'data.root.reposted_by_me',
                false
            )
            ->assertJsonPath(
                'data.replies.0.reposts_count',
                1
            )
            ->assertJsonPath(
                'data.replies.0.reposted_by_me',
                true
            );
    }

    public function test_reply_can_be_reposted(): void
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
                "/api/posts/{$reply->id}/repost"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.reposted',
                true
            );
    }
}
