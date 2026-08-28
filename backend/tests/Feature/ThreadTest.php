<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_returns_root_and_all_replies(): void
    {
        $root =
            Post::factory()->create([
                'content' => 'Root',
            ]);

        $reply1 =
            Post::factory()->create([
                'content' => 'Reply 1',
            ]);

        $reply1->parent_post_id = $root->id;

        $reply1->root_post_id = $root->id;

        $reply1->save();

        $reply2 =
            Post::factory()->create([
                'content' => 'Reply 2',
            ]);

        $reply2->parent_post_id = $reply1->id;

        $reply2->root_post_id = $root->id;

        $reply2->save();

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
            ->assertJsonCount(
                2,
                'data.replies'
            );
    }

    public function test_thread_can_be_loaded_from_nested_reply(): void
    {
        $root =
            Post::factory()->create();

        $reply =
            Post::factory()->create();

        $reply->parent_post_id =
            $root->id;

        $reply->root_post_id =
            $root->id;

        $reply->save();

        $nested =
            Post::factory()->create();

        $nested->parent_post_id =
            $reply->id;

        $nested->root_post_id =
            $root->id;

        $nested->save();

        $this
            ->getJson(
                "/api/posts/{$nested->id}/thread"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.root.id',
                $root->id
            )
            ->assertJsonCount(
                2,
                'data.replies'
            );
    }

    public function test_root_is_not_duplicated_in_replies(): void
    {
        $root =
            Post::factory()->create();

        $this
            ->getJson(
                "/api/posts/{$root->id}/thread"
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.replies'
            );
    }

    public function test_unknown_thread_returns_404(): void
    {
        $this
            ->getJson(
                '/api/posts/999999/thread'
            )
            ->assertNotFound();
    }
}
