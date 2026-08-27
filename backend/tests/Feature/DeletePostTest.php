<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeletePostTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create();

        Sanctum::actingAs($user);

        $this
            ->deleteJson(
                "/api/posts/{$post->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'posts',
            [
                'id' => $post->id,
            ]
        );
    }

    public function test_user_cannot_delete_another_users_post(): void
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
            ->deleteJson(
                "/api/posts/{$post->id}"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $post->id,
            ]
        );
    }

    public function test_guest_cannot_delete_post(): void
    {
        $post =
            Post::factory()->create();

        $this
            ->deleteJson(
                "/api/posts/{$post->id}"
            )
            ->assertUnauthorized();
    }

    public function test_post_media_rows_are_deleted_with_post(): void
    {
        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create();

        $media =
            $post->media()->create([
                'type' => 'image',
                'path' => 'posts/test/image.jpg',
                'sort_order' => 0,
            ]);

        Sanctum::actingAs($user);

        $this
            ->deleteJson(
                "/api/posts/{$post->id}"
            )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'post_media',
            [
                'id' => $media->id,
            ]
        );
    }

    public function test_post_media_files_are_deleted(): void
    {
        Storage::fake('public');

        $user =
            User::factory()->create();

        $post =
            Post::factory()
                ->for($user)
                ->create();

        $path =
            "posts/{$post->id}/image.jpg";

        Storage::disk('public')
            ->put(
                $path,
                'fake-image'
            );

        $post->media()->create([
            'type' => 'image',
            'path' => $path,
            'sort_order' => 0,
        ]);

        Sanctum::actingAs($user);

        $this
            ->deleteJson(
                "/api/posts/{$post->id}"
            )
            ->assertNoContent();

        Storage::disk('public')
            ->assertMissing($path);
    }

    public function test_delete_unknown_post_returns_404(): void
    {
        $user =
            User::factory()->create();

        Sanctum::actingAs($user);

        $this
            ->deleteJson(
                '/api/posts/999999'
            )
            ->assertNotFound();
    }
}
