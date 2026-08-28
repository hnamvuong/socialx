<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_post_can_be_viewed(): void
    {
        $user =
            User::factory()->create([
                'username' => 'alice',
                'display_name' => 'Alice',
            ]);

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'content' => 'Hello SocialX',
                ]);

        $response =
            $this->getJson(
                "/api/posts/{$post->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.post.id',
                $post->id
            )
            ->assertJsonPath(
                'data.post.content',
                'Hello SocialX'
            )
            ->assertJsonPath(
                'data.post.user.username',
                'alice'
            );
    }

    public function test_post_detail_does_not_require_authentication(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertOk();
    }

    public function test_unknown_post_returns_404(): void
    {
        $this
            ->getJson(
                '/api/posts/999999'
            )
            ->assertNotFound();
    }

    public function test_post_detail_returns_media_in_order(): void
    {
        $post =
            Post::factory()->create();

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/example-second.jpg',
            'sort_order' => 1,
        ]);

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/example-first.jpg',
            'sort_order' => 0,
        ]);

        $response =
            $this->getJson(
                "/api/posts/{$post->id}"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.post.media.0.sort_order',
                0
            )
            ->assertJsonPath(
                'data.post.media.1.sort_order',
                1
            );
    }

    public function test_post_of_inactive_user_is_not_public(): void
    {
        $user =
            User::factory()->create();

        $user->status =
            'suspended';

        $user->save();

        $post =
            Post::factory()
                ->for($user)
                ->create();

        $this
            ->getJson(
                "/api/posts/{$post->id}"
            )
            ->assertNotFound();
    }
}
