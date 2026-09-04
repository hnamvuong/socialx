<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'user')
            ->firstOrFail();

        $user->roles()->attach($role);

        Sanctum::actingAs($user);

        return $user;
    }

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

    public function test_creating_post_persists_hashtags(): void
    {
        $this->actingAsUser();

        $response =
            $this->postJson(
                '/api/posts',
                [
                    'content' => 'Học #Laravel và #Vue3',
                ]
            );

        $response
            ->assertCreated();

        $post =
            Post::query()
                ->latest('id')
                ->firstOrFail();

        $this->assertDatabaseHas(
            'hashtags',
            [
                'name' => 'laravel',
            ]
        );

        $this->assertDatabaseHas(
            'hashtags',
            [
                'name' => 'vue3',
            ]
        );

        $laravel =
            Hashtag::query()
                ->where(
                    'name',
                    'laravel'
                )
                ->firstOrFail();

        $vue =
            Hashtag::query()
                ->where(
                    'name',
                    'vue3'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'post_hashtags',
            [
                'post_id' => $post->id,

                'hashtag_id' => $laravel->id,
            ]
        );

        $this->assertDatabaseHas(
            'post_hashtags',
            [
                'post_id' => $post->id,

                'hashtag_id' => $vue->id,
            ]
        );
    }

    public function test_creating_post_does_not_duplicate_same_hashtag(): void
    {
        $this->actingAsUser();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => '#Laravel #laravel #LARAVEL',
                ]
            )
            ->assertCreated();

        $this->assertSame(
            1,
            Hashtag::query()
                ->where(
                    'name',
                    'laravel'
                )
                ->count()
        );

        $post =
            Post::query()
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            1,
            $post
                ->hashtags()
                ->count()
        );
    }

    public function test_updating_post_syncs_hashtags(): void
    {
        $user = $this->actingAsUser();

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'content' => 'Học #laravel',
                ]);

        $laravel =
            new Hashtag;

        $laravel->name =
            'laravel';

        $laravel->save();

        $post
            ->hashtags()
            ->attach(
                $laravel->id
            );

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => 'Chuyển sang #vue3',
                ]
            )
            ->assertOk();

        $post->refresh();

        $this->assertFalse(
            $post
                ->hashtags()
                ->where(
                    'name',
                    'laravel'
                )
                ->exists()
        );

        $this->assertTrue(
            $post
                ->hashtags()
                ->where(
                    'name',
                    'vue3'
                )
                ->exists()
        );
    }

    public function test_reply_persists_hashtags(): void
    {
        $user = $this->actingAsUser();

        $parent =
            Post::factory()
                ->for($user)
                ->create();

        $response =
            $this->postJson(
                "/api/posts/{$parent->id}/replies",
                [
                    'content' => 'Reply bằng #laravel',
                ]
            );

        $response
            ->assertCreated();

        $replyId =
            $response->json(
                'data.post.id'
            );

        $reply =
            Post::query()
                ->findOrFail(
                    $replyId
                );

        $this->assertTrue(
            $reply
                ->hashtags()
                ->where(
                    'name',
                    'laravel'
                )
                ->exists()
        );
    }
}
