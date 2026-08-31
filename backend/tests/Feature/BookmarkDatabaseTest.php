<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bookmark_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        $bookmark =
            Bookmark::factory()->create([
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]);

        $this->assertTrue(
            $bookmark->user->is(
                $user
            )
        );

        $this->assertTrue(
            $bookmark->post->is(
                $post
            )
        );
    }

    public function test_same_post_cannot_be_bookmarked_twice_by_same_user(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Bookmark::factory()->create([
            'user_id' => $user->id,

            'post_id' => $post->id,
        ]);

        $this->expectException(
            QueryException::class
        );

        Bookmark::factory()->create([
            'user_id' => $user->id,

            'post_id' => $post->id,
        ]);
    }

    public function test_user_can_bookmark_multiple_posts(): void
    {
        $user =
            User::factory()->create();

        $first =
            Post::factory()->create();

        $second =
            Post::factory()->create();

        Bookmark::factory()->create([
            'user_id' => $user->id,

            'post_id' => $first->id,
        ]);

        Bookmark::factory()->create([
            'user_id' => $user->id,

            'post_id' => $second->id,
        ]);

        $this->assertSame(
            2,
            $user
                ->bookmarks()
                ->count()
        );
    }

    public function test_multiple_users_can_bookmark_same_post(): void
    {
        $firstUser =
            User::factory()->create();

        $secondUser =
            User::factory()->create();

        $post =
            Post::factory()->create();

        Bookmark::factory()->create([
            'user_id' => $firstUser->id,

            'post_id' => $post->id,
        ]);

        Bookmark::factory()->create([
            'user_id' => $secondUser->id,

            'post_id' => $post->id,
        ]);

        $this->assertSame(
            2,
            $post
                ->bookmarks()
                ->count()
        );
    }

    public function test_bookmarks_are_deleted_when_post_is_deleted(): void
    {
        $bookmark =
            Bookmark::factory()->create();

        $post =
            $bookmark->post;

        $post->delete();

        $this->assertDatabaseMissing(
            'bookmarks',
            [
                'id' => $bookmark->id,
            ]
        );
    }

    public function test_bookmarks_are_deleted_when_user_is_deleted(): void
    {
        $bookmark =
            Bookmark::factory()->create();

        $user =
            $bookmark->user;

        $user->delete();

        $this->assertDatabaseMissing(
            'bookmarks',
            [
                'id' => $bookmark->id,
            ]
        );
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

        Bookmark::factory()->create([
            'user_id' => $user->id,

            'post_id' => $reply->id,
        ]);

        $this->assertSame(
            1,
            $reply
                ->bookmarks()
                ->count()
        );
    }
}
