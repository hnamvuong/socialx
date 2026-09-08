<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LikeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_liking_another_users_post_creates_notification(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($owner)
                ->create();

        Sanctum::actingAs(
            $actor
        );

        $response =
            $this->postJson(
                "/api/posts/{$post->id}/like"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $owner->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_LIKE,

                'post_id' => $post->id,

                'read_at' => null,
            ]
        );
    }

    public function test_liking_own_post_does_not_create_notification(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($user)
                ->create();

        Sanctum::actingAs(
            $user
        );

        $response =
            $this->postJson(
                "/api/posts/{$post->id}/like"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                true
            );

        $this->assertDatabaseHas(
            'likes',
            [
                'user_id' => $user->id,

                'post_id' => $post->id,
            ]
        );

        $this->assertDatabaseCount(
            'notifications',
            0
        );
    }

    public function test_repeated_like_does_not_create_duplicate_notification(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($owner)
                ->create();

        Sanctum::actingAs(
            $actor
        );

        $this->postJson(
            "/api/posts/{$post->id}/like"
        )
            ->assertOk();

        $this->postJson(
            "/api/posts/{$post->id}/like"
        )
            ->assertOk();

        $this->assertDatabaseCount(
            'notifications',
            1
        );

        $this->assertDatabaseCount(
            'likes',
            1
        );
    }

    public function test_unlike_removes_like_notification(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($owner)
                ->create();

        Sanctum::actingAs(
            $actor
        );

        $this->postJson(
            "/api/posts/{$post->id}/like"
        )
            ->assertOk();

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $owner->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_LIKE,

                'post_id' => $post->id,
            ]
        );

        $response =
            $this->deleteJson(
                "/api/posts/{$post->id}/like"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                false
            );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $owner->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_LIKE,

                'post_id' => $post->id,
            ]
        );

        $this->assertDatabaseMissing(
            'likes',
            [
                'user_id' => $actor->id,

                'post_id' => $post->id,
            ]
        );
    }

    public function test_unlike_is_idempotent(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($owner)
                ->create();

        Sanctum::actingAs(
            $actor
        );

        $this->deleteJson(
            "/api/posts/{$post->id}/like"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.liked',
                false
            );

        $this->assertDatabaseCount(
            'likes',
            0
        );

        $this->assertDatabaseCount(
            'notifications',
            0
        );
    }
}
