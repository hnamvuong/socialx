<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdatePostTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_post_content(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'content' => 'Nội dung cũ',
                ]);

        Sanctum::actingAs($user);

        $response =
            $this->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => 'Nội dung mới',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.post.content',
                'Nội dung mới'
            );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $post->id,

                'content' => 'Nội dung mới',
            ]
        );
    }

    public function test_user_cannot_update_another_users_post(): void
    {
        $owner =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($owner)
                ->create();

        Sanctum::actingAs(
            $otherUser
        );

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => 'Hacked content',
                ]
            )
            ->assertForbidden();

        $post->refresh();

        $this->assertNotSame(
            'Hacked content',
            $post->content
        );
    }

    public function test_guest_cannot_update_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => 'New content',
                ]
            )
            ->assertUnauthorized();
    }

    public function test_updated_post_content_cannot_exceed_280_characters(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create();

        Sanctum::actingAs($user);

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => str_repeat(
                        'a',
                        281
                    ),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content',
            ]);
    }

    public function test_text_only_post_cannot_become_empty(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'content' => 'Hello',
                ]);

        Sanctum::actingAs($user);

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => '      ',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content',
            ]);
    }

    public function test_post_with_media_can_have_empty_content(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'content' => 'Caption',
                ]);

        $post->media()->create([
            'type' => 'image',
            'path' => 'posts/example.jpg',
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($user);

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => '',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.content',
                null
            );
    }
}
