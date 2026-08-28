<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_post_has_no_parent_or_root(): void
    {
        $post = Post::factory()->create();

        $this->assertNull(
            $post->parent_post_id
        );

        $this->assertNull(
            $post->root_post_id
        );
    }

    public function test_post_can_reply_to_another_post(): void
    {
        $root = Post::factory()->create();

        $reply = Post::factory()->create();

        $reply->parent_post_id = $root->id;

        $reply->root_post_id = $root->id;

        $reply->save();

        $reply->refresh();

        $this->assertSame(
            $root->id,
            $reply->parent_post_id
        );

        $this->assertSame(
            $root->id,
            $reply->root_post_id
        );

        $this->assertTrue(
            $reply->parent->is($root)
        );

        $this->assertTrue(
            $reply->root->is($root)
        );
    }

    public function test_nested_reply_keeps_original_root(): void
    {
        $root = Post::factory()->create();

        $firstReply = Post::factory()->create();

        $firstReply->parent_post_id = $root->id;

        $firstReply->root_post_id = $root->id;

        $firstReply->save();

        $secondReply = Post::factory()->create();

        $secondReply->parent_post_id = $firstReply->id;

        $secondReply->root_post_id =
            $firstReply->root_post_id
            ?? $firstReply->id;

        $secondReply->save();

        $secondReply->refresh();

        $this->assertSame(
            $firstReply->id,
            $secondReply->parent_post_id
        );

        $this->assertSame(
            $root->id,
            $secondReply->root_post_id
        );

        $this->assertTrue(
            $secondReply
                ->parent
                ->is($firstReply)
        );

        $this->assertTrue(
            $secondReply
                ->root
                ->is($root)
        );
    }

    public function test_replies_relationship_only_returns_direct_children(): void
    {
        $root = Post::factory()->create();

        $firstReply = Post::factory()->create();

        $firstReply->parent_post_id = $root->id;

        $firstReply->root_post_id = $root->id;

        $firstReply->save();

        $nestedReply = Post::factory()->create();

        $nestedReply->parent_post_id = $firstReply->id;

        $nestedReply->root_post_id = $root->id;

        $nestedReply->save();

        $root->refresh();

        $replyIds =
            $root
                ->replies()
                ->pluck('id')
                ->all();

        $this->assertContains(
            $firstReply->id,
            $replyIds
        );

        $this->assertNotContains(
            $nestedReply->id,
            $replyIds
        );
    }

    public function test_root_post_id_can_query_all_thread_replies(): void
    {
        $root = Post::factory()->create();

        $reply1 = Post::factory()->create();

        $reply1->parent_post_id = $root->id;

        $reply1->root_post_id = $root->id;

        $reply1->save();

        $reply2 = Post::factory()->create();

        $reply2->parent_post_id = $reply1->id;

        $reply2->root_post_id = $root->id;

        $reply2->save();

        $threadReplyIds =
            Post::query()
                ->where(
                    'root_post_id',
                    $root->id
                )
                ->orderBy('created_at')
                ->pluck('id')
                ->all();

        $this->assertContains(
            $reply1->id,
            $threadReplyIds
        );

        $this->assertContains(
            $reply2->id,
            $threadReplyIds
        );

        $this->assertNotContains(
            $root->id,
            $threadReplyIds
        );
    }

    public function test_reply_survives_when_parent_is_deleted(): void
    {
        $root = Post::factory()->create();

        $reply = Post::factory()->create();

        $reply->parent_post_id = $root->id;

        $reply->root_post_id = $root->id;

        $reply->save();

        $root->delete();

        $reply->refresh();

        $this->assertNull(
            $reply->parent_post_id
        );

        $this->assertNull(
            $reply->root_post_id
        );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $reply->id,
            ]
        );
    }

    public function test_reply_has_its_own_author(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $root =
            Post::factory()
                ->for($alice)
                ->create();

        $reply =
            Post::factory()
                ->for($bob)
                ->create();

        $reply->parent_post_id = $root->id;

        $reply->root_post_id = $root->id;

        $reply->save();

        $this->assertTrue(
            $reply->user->is($bob)
        );

        $this->assertTrue(
            $root->user->is($alice)
        );
    }
}
