<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_posts_by_content(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $matchingPost =
            Post::factory()
                ->for($author)
                ->create([
                    'content' => 'Tôi đang học Laravel',
                ]);

        Post::factory()
            ->for($author)
            ->create([
                'content' => 'Vue TypeScript',
            ]);

        $response =
            $this->getJson(
                '/api/search/posts?q=Laravel'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $matchingPost->id
            );
    }

    public function test_post_search_is_case_insensitive(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $post =
            Post::factory()
                ->for($author)
                ->create([
                    'content' => 'Laravel Vue SocialX',
                ]);

        $this
            ->getJson(
                '/api/search/posts?q=LARAVEL'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.posts.0.id',
                $post->id
            );
    }

    public function test_it_does_not_return_non_matching_posts(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        Post::factory()
            ->for($author)
            ->create([
                'content' => 'Laravel framework',
            ]);

        $response =
            $this->getJson(
                '/api/search/posts?q=Python'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_replies_are_not_returned_in_post_search(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $root =
            Post::factory()
                ->for($author)
                ->create([
                    'content' => 'Root post',
                ]);

        Post::factory()
            ->for($author)
            ->create([
                'content' => 'Laravel reply',

                'parent_post_id' => $root->id,

                'root_post_id' => $root->id,
            ]);

        $response =
            $this->getJson(
                '/api/search/posts?q=Laravel'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_posts_from_inactive_users_are_not_returned(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'inactive',

                    'is_private' => false,
                ]);

        Post::factory()
            ->for($author)
            ->create([
                'content' => 'Laravel content',
            ]);

        $response =
            $this->getJson(
                '/api/search/posts?q=Laravel'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_guest_cannot_search_private_user_posts(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => true,
                ]);

        Post::factory()
            ->for($author)
            ->create([
                'content' => 'Laravel private content',
            ]);

        $response =
            $this->getJson(
                '/api/search/posts?q=Laravel'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_search_query_is_required(): void
    {
        $this
            ->getJson(
                '/api/search/posts'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }

    public function test_blank_search_query_is_rejected(): void
    {
        $this
            ->getJson(
                '/api/search/posts?q=%20%20%20'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }

    public function test_post_search_uses_cursor_pagination(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        Post::factory()
            ->count(21)
            ->for($author)
            ->create([
                'content' => 'Laravel searchable post',
            ]);

        $response =
            $this->getJson(
                '/api/search/posts?q=Laravel'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.posts'
            );

        $this->assertNotNull(
            $response->json(
                'data.pagination.next_cursor'
            )
        );

        $this->assertTrue(
            $response->json(
                'data.pagination.has_more'
            )
        );
    }

    public function test_post_search_can_load_next_cursor(): void
    {
        $author =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        Post::factory()
            ->count(21)
            ->for($author)
            ->create([
                'content' => 'Laravel searchable',
            ]);

        $firstResponse =
            $this->getJson(
                '/api/search/posts?q=Laravel'
            );

        $cursor =
            $firstResponse->json(
                'data.pagination.next_cursor'
            );

        $secondResponse =
            $this->getJson(
                '/api/search/posts?q=Laravel&cursor='
                .urlencode(
                    $cursor
                )
            );

        $secondResponse
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts'
            );
    }
}
