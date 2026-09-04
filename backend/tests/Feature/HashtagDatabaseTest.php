<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashtagDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_hashtag_can_be_created(): void
    {
        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $this->assertDatabaseHas(
            'hashtags',
            [
                'id' => $hashtag->id,

                'name' => 'laravel',
            ]
        );
    }

    public function test_hashtag_name_must_be_unique(): void
    {
        $first =
            new Hashtag;

        $first->name =
            'laravel';

        $first->save();

        $this->expectException(
            QueryException::class
        );

        $duplicate =
            new Hashtag;

        $duplicate->name =
            'laravel';

        $duplicate->save();
    }

    public function test_post_can_have_hashtags(): void
    {
        $post =
            Post::factory()
                ->create();

        $laravel =
            new Hashtag;

        $laravel->name =
            'laravel';

        $laravel->save();

        $vue =
            new Hashtag;

        $vue->name =
            'vue3';

        $vue->save();

        $post
            ->hashtags()
            ->attach([
                $laravel->id,
                $vue->id,
            ]);

        $post->load(
            'hashtags'
        );

        $this->assertCount(
            2,
            $post->hashtags
        );

        $this->assertTrue(
            $post
                ->hashtags
                ->contains(
                    $laravel
                )
        );

        $this->assertTrue(
            $post
                ->hashtags
                ->contains(
                    $vue
                )
        );
    }

    public function test_hashtag_can_have_multiple_posts(): void
    {
        $firstPost =
            Post::factory()
                ->create();

        $secondPost =
            Post::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'programming';

        $hashtag->save();

        $hashtag
            ->posts()
            ->attach([
                $firstPost->id,
                $secondPost->id,
            ]);

        $hashtag->load(
            'posts'
        );

        $this->assertCount(
            2,
            $hashtag->posts
        );
    }

    public function test_same_hashtag_cannot_be_attached_twice_to_same_post(): void
    {
        $post =
            Post::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $post
            ->hashtags()
            ->attach(
                $hashtag->id
            );

        $this->expectException(
            QueryException::class
        );

        $post
            ->hashtags()
            ->attach(
                $hashtag->id
            );
    }

    public function test_deleting_post_removes_post_hashtag_rows(): void
    {
        $post =
            Post::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $post
            ->hashtags()
            ->attach(
                $hashtag->id
            );

        $postId =
            $post->id;

        $post->delete();

        $this->assertDatabaseMissing(
            'post_hashtags',
            [
                'post_id' => $postId,

                'hashtag_id' => $hashtag->id,
            ]
        );

        /*
         * Xóa Post không được xóa Hashtag,
         * vì Hashtag có thể đang được Post khác sử dụng.
         */
        $this->assertDatabaseHas(
            'hashtags',
            [
                'id' => $hashtag->id,
            ]
        );
    }

    public function test_deleting_hashtag_removes_pivot_rows(): void
    {
        $post =
            Post::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $post
            ->hashtags()
            ->attach(
                $hashtag->id
            );

        $hashtagId =
            $hashtag->id;

        $hashtag->delete();

        $this->assertDatabaseMissing(
            'post_hashtags',
            [
                'post_id' => $post->id,

                'hashtag_id' => $hashtagId,
            ]
        );
    }
}
