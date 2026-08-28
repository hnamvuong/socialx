<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $like =
            Like::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $this->assertTrue(
            $like->user->is(
                $user
            )
        );

        $this->assertTrue(
            $like->post->is(
                $post
            )
        );
    }

    public function test_same_user_cannot_like_same_post_twice(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,

            'post_id' => $post->id,
        ]);

        $this->expectException(
            QueryException::class
        );

        Like::factory()->create([
            'user_id' => $user->id,

            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_like_multiple_posts(): void
    {
        $user =
            User::factory()->create();

        $firstPost =
            Post::factory()->create();

        $secondPost =
            Post::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,

            'post_id' => $firstPost->id,
        ]);

        Like::factory()->create([
            'user_id' => $user->id,

            'post_id' => $secondPost->id,
        ]);

        $this->assertSame(
            2,
            $user
                ->likes()
                ->count()
        );
    }

    public function test_multiple_users_can_like_same_post(): void
    {
        $firstUser =
            User::factory()->create();

        $secondUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Like::factory()->create([
            'user_id' => $firstUser->id,

            'post_id' => $post->id,
        ]);

        Like::factory()->create([
            'user_id' => $secondUser->id,

            'post_id' => $post->id,
        ]);

        $this->assertSame(
            2,
            $post
                ->likes()
                ->count()
        );
    }

    public function test_user_can_access_liked_posts(): void
    {
        $user =
            User::factory()->create();

        $likedPost =
            Post::factory()->create();

        $unlikedPost =
            Post::factory()->create();

        Like::factory()->create([
            'user_id' => $user->id,

            'post_id' => $likedPost->id,
        ]);

        $likedPostIds =
            $user
                ->likedPosts()
                ->pluck('posts.id')
                ->all();

        $this->assertContains(
            $likedPost->id,
            $likedPostIds
        );

        $this->assertNotContains(
            $unlikedPost->id,
            $likedPostIds
        );
    }

    public function test_post_can_access_users_who_liked_it(): void
    {
        $likedBy =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Like::factory()->create([
            'user_id' => $likedBy->id,

            'post_id' => $post->id,
        ]);

        $userIds =
            $post
                ->likedByUsers()
                ->pluck('users.id')
                ->all();

        $this->assertContains(
            $likedBy->id,
            $userIds
        );

        $this->assertNotContains(
            $otherUser->id,
            $userIds
        );
    }

    public function test_likes_are_deleted_when_post_is_deleted(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $like =
            Like::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $post->delete();

        $this->assertDatabaseMissing(
            'likes',
            [
                'id' => $like->id,
            ]
        );
    }

    public function test_likes_are_deleted_when_user_is_deleted(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $like =
            Like::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $user->delete();

        $this->assertDatabaseMissing(
            'likes',
            [
                'id' => $like->id,
            ]
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

        Like::factory()->create([
            'user_id' => $user->id,

            'post_id' => $reply->id,
        ]);

        $this->assertSame(
            1,
            $reply
                ->likes()
                ->count()
        );
    }
}
