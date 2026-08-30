<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuotePostTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $this->seed();

        $user =
            User::factory()->create();

        $role =
            Role::query()
                ->where(
                    'name',
                    'user'
                )
                ->firstOrFail();

        $user
            ->roles()
            ->attach(
                $role->id
            );

        Sanctum::actingAs(
            $user
        );

        return $user;
    }

    public function test_user_can_create_quote_post(): void
    {
        $user =
            $this->actingAsUser();

        $original =
            Post::factory()->create([
                'content' => 'Original post',
            ]);

        $response =
            $this->postJson(
                '/api/posts',
                [
                    'content' => 'My comment',

                    'quoted_post_id' => $original->id,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.post.content',
                'My comment'
            )
            ->assertJsonPath(
                'data.post.quoted_post_id',
                $original->id
            )
            ->assertJsonPath(
                'data.post.quoted_post.id',
                $original->id
            );

        $this->assertDatabaseHas(
            'posts',
            [
                'user_id' => $user->id,

                'content' => 'My comment',

                'quoted_post_id' => $original->id,
            ]
        );
    }

    public function test_quote_post_is_owned_by_authenticated_user(): void
    {
        $user =
            $this->actingAsUser();

        $original =
            Post::factory()->create();

        $response =
            $this->postJson(
                '/api/posts',
                [
                    'content' => 'Quote',

                    'quoted_post_id' => $original->id,
                ]
            );

        $postId =
            $response
                ->json(
                    'data.post.id'
                );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $postId,

                'user_id' => $user->id,

                'quoted_post_id' => $original->id,
            ]
        );
    }

    public function test_quote_unknown_post_returns_validation_error(): void
    {
        $this->actingAsUser();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Quote',

                    'quoted_post_id' => 999999,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'quoted_post_id',
            ]);
    }

    public function test_cannot_quote_post_of_inactive_user(): void
    {
        $this->actingAsUser();

        $author =
            User::factory()->create();

        $author->status =
            'suspended';

        $author->save();

        $original =
            Post::factory()
                ->for($author)
                ->create();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Quote',

                    'quoted_post_id' => $original->id,
                ]
            )
            ->assertNotFound();
    }

    public function test_reply_can_be_quoted(): void
    {
        $this->actingAsUser();

        $root =
            Post::factory()->create();

        $reply =
            Post::factory()->create();

        $reply->parent_post_id =
            $root->id;

        $reply->root_post_id =
            $root->id;

        $reply->save();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Quote reply',

                    'quoted_post_id' => $reply->id,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.post.quoted_post_id',
                $reply->id
            );
    }

    public function test_quote_post_can_quote_another_quote_post(): void
    {
        $this->actingAsUser();

        $original =
            Post::factory()->create();

        $firstQuote =
            Post::factory()->create([
                'content' => 'First quote',
            ]);

        $firstQuote->quoted_post_id =
            $original->id;

        $firstQuote->save();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Second quote',

                    'quoted_post_id' => $firstQuote->id,
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.post.quoted_post_id',
                $firstQuote->id
            );
    }

    public function test_deleting_original_post_does_not_delete_quote(): void
    {
        $original =
            Post::factory()->create();

        $quote =
            Post::factory()->create([
                'content' => 'My quote',
            ]);

        $quote->quoted_post_id =
            $original->id;

        $quote->save();

        $original->delete();

        $quote->refresh();

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $quote->id,
            ]
        );

        $this->assertNull(
            $quote->quoted_post_id
        );
    }

    public function test_post_detail_returns_quoted_post(): void
    {
        $original =
            Post::factory()->create([
                'content' => 'Original',
            ]);

        $quote =
            Post::factory()->create([
                'content' => 'Comment',
            ]);

        $quote->quoted_post_id =
            $original->id;

        $quote->save();

        $this
            ->getJson(
                "/api/posts/{$quote->id}"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.post.quoted_post.id',
                $original->id
            )
            ->assertJsonPath(
                'data.post.quoted_post.content',
                'Original'
            );
    }

    public function test_thread_returns_quoted_post_data(): void
    {
        $original =
            Post::factory()->create([
                'content' => 'Original',
            ]);

        $quote =
            Post::factory()->create([
                'content' => 'Quote',
            ]);

        $quote->quoted_post_id =
            $original->id;

        $quote->save();

        $this
            ->getJson(
                "/api/posts/{$quote->id}/thread"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.root.quoted_post.id',
                $original->id
            );
    }
}
