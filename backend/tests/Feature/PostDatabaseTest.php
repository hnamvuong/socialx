<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_posts(): void
    {
        $user = User::factory()->create();

        $post = $user
            ->posts()
            ->create([
                'content' => 'Hello SocialX',
            ]);

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $post->id,
                'user_id' => $user->id,
                'content' => 'Hello SocialX',
            ]
        );

        $this->assertTrue(
            $post->user->is($user)
        );
    }

    public function test_post_can_have_media(): void
    {
        $user = User::factory()->create();

        $post = $user
            ->posts()
            ->create([
                'content' => 'Post có hình ảnh',
            ]);

        $media = $post
            ->media()
            ->create([
                'type' => 'image',
                'path' => 'posts/example.jpg',
                'mime_type' => 'image/jpeg',
                'width' => 1200,
                'height' => 800,
                'sort_order' => 0,
            ]);

        $this->assertDatabaseHas(
            'post_media',
            [
                'id' => $media->id,
                'post_id' => $post->id,
                'path' => 'posts/example.jpg',
            ]
        );

        $this->assertTrue(
            $media->post->is($post)
        );
    }

    public function test_post_media_is_ordered_by_sort_order(): void
    {
        $post = Post::factory()->create();

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/second.jpg',
            'sort_order' => 1,
        ]);

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/first.jpg',
            'sort_order' => 0,
        ]);

        $orders = $post
            ->media()
            ->pluck('sort_order')
            ->all();

        $this->assertSame(
            [0, 1],
            $orders
        );
    }

    public function test_same_post_cannot_have_duplicate_media_order(): void
    {
        $post = Post::factory()->create();

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/first.jpg',
            'sort_order' => 0,
        ]);

        $this->expectException(
            QueryException::class
        );

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/second.jpg',
            'sort_order' => 0,
        ]);
    }

    public function test_post_media_is_deleted_when_post_is_deleted(): void
    {
        $post = Post::factory()->create();

        $media = $post
            ->media()
            ->create([
                'type' => 'image',
                'path' => 'posts/example.jpg',
                'sort_order' => 0,
            ]);

        $post->delete();

        $this->assertDatabaseMissing(
            'post_media',
            [
                'id' => $media->id,
            ]
        );
    }

    public function test_posts_are_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()->create();

        $post = Post::factory()
            ->for($user)
            ->create();

        $user->delete();

        $this->assertDatabaseMissing(
            'posts',
            [
                'id' => $post->id,
            ]
        );
    }
}
