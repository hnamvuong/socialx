<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepostDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_repost_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $repost =
            Repost::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $this->assertTrue(
            $repost->user->is(
                $user
            )
        );

        $this->assertTrue(
            $repost->post->is(
                $post
            )
        );

        $this->assertDatabaseHas(
            'reposts',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_same_user_cannot_repost_same_post_twice(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Repost::factory()->create([
            'user_id' => $user->id,

            'post_id' => $post->id,
        ]);

        $this->expectException(
            QueryException::class
        );

        Repost::factory()->create([
            'user_id' => $user->id,

            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_repost_multiple_posts(): void
    {
        $user =
            User::factory()->create();

        $firstPost =
            Post::factory()->create();

        $secondPost =
            Post::factory()->create();

        Repost::factory()->create([
            'user_id' => $user->id,

            'post_id' => $firstPost->id,
        ]);

        Repost::factory()->create([
            'user_id' => $user->id,

            'post_id' => $secondPost->id,
        ]);

        $this->assertSame(
            2,
            $user
                ->reposts()
                ->count()
        );
    }

    public function test_multiple_users_can_repost_same_post(): void
    {
        $firstUser =
            User::factory()->create();

        $secondUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Repost::factory()->create([
            'user_id' => $firstUser->id,

            'post_id' => $post->id,
        ]);

        Repost::factory()->create([
            'user_id' => $secondUser->id,

            'post_id' => $post->id,
        ]);

        $this->assertSame(
            2,
            $post
                ->reposts()
                ->count()
        );
    }

    public function test_user_can_access_reposted_posts(): void
    {
        $user =
            User::factory()->create();

        $repostedPost =
            Post::factory()->create();

        $otherPost =
            Post::factory()->create();

        Repost::factory()->create([
            'user_id' => $user->id,

            'post_id' => $repostedPost->id,
        ]);

        $postIds =
            $user
                ->repostedPosts()
                ->pluck('posts.id')
                ->all();

        $this->assertContains(
            $repostedPost->id,
            $postIds
        );

        $this->assertNotContains(
            $otherPost->id,
            $postIds
        );
    }

    public function test_post_can_access_users_who_reposted_it(): void
    {
        $reposter =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Repost::factory()->create([
            'user_id' => $reposter->id,

            'post_id' => $post->id,
        ]);

        $userIds =
            $post
                ->repostedByUsers()
                ->pluck('users.id')
                ->all();

        $this->assertContains(
            $reposter->id,
            $userIds
        );

        $this->assertNotContains(
            $otherUser->id,
            $userIds
        );
    }

    public function test_reposts_are_deleted_when_post_is_deleted(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $repost =
            Repost::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $post->delete();

        $this->assertDatabaseMissing(
            'reposts',
            [
                'id' => $repost->id,
            ]
        );
    }

    public function test_reposts_are_deleted_when_user_is_deleted(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $repost =
            Repost::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $user->delete();

        $this->assertDatabaseMissing(
            'reposts',
            [
                'id' => $repost->id,
            ]
        );
    }

    public function test_reply_can_be_reposted_like_normal_post(): void
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

        Repost::factory()->create([
            'user_id' => $user->id,

            'post_id' => $reply->id,
        ]);

        $this->assertSame(
            1,
            $reply
                ->reposts()
                ->count()
        );
    }
}
