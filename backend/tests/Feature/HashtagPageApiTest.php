<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashtagPageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_posts_for_hashtag(): void
    {
        $author =
            User::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->hashtags()
            ->attach(
                $hashtag->id
            );

        $this
            ->getJson(
                '/api/hashtags/laravel/posts'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.hashtag.name',
                'laravel'
            )
            ->assertJsonPath(
                'data.posts.0.id',
                $post->id
            );
    }

    public function test_unknown_hashtag_returns_not_found(): void
    {
        $this
            ->getJson(
                '/api/hashtags/not-found/posts'
            )
            ->assertNotFound();
    }

    public function test_it_does_not_return_posts_from_other_hashtags(): void
    {
        $author =
            User::factory()
                ->create();

        $laravel =
            new Hashtag;

        $laravel->name =
            'laravel';

        $laravel->save();

        $vue =
            new Hashtag;

        $vue->name =
            'vue';

        $vue->save();

        $laravelPost =
            Post::factory()
                ->for($author)
                ->create();

        $vuePost =
            Post::factory()
                ->for($author)
                ->create();

        $laravelPost
            ->hashtags()
            ->attach(
                $laravel->id
            );

        $vuePost
            ->hashtags()
            ->attach(
                $vue->id
            );

        $response =
            $this->getJson(
                '/api/hashtags/laravel/posts'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.posts'
            )
            ->assertJsonPath(
                'data.posts.0.id',
                $laravelPost->id
            );
    }

    public function test_hashtag_page_does_not_return_replies(): void
    {
        $author =
            User::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $rootPost =
            Post::factory()
                ->for($author)
                ->create();

        $reply =
            Post::factory()
                ->for($author)
                ->create([
                    'parent_post_id' => $rootPost->id,

                    'root_post_id' => $rootPost->id,
                ]);

        $reply
            ->hashtags()
            ->attach(
                $hashtag->id
            );

        $this
            ->getJson(
                '/api/hashtags/laravel/posts'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_guest_cannot_see_private_user_posts(): void
    {
        $privateAuthor =
            User::factory()
                ->create([
                    'is_private' => true,
                ]);

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $post =
            Post::factory()
                ->for($privateAuthor)
                ->create();

        $post
            ->hashtags()
            ->attach(
                $hashtag->id
            );

        $this
            ->getJson(
                '/api/hashtags/laravel/posts'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.posts'
            );
    }

    public function test_hashtag_posts_use_cursor_pagination(): void
    {
        $author =
            User::factory()
                ->create();

        $hashtag =
            new Hashtag;

        $hashtag->name =
            'laravel';

        $hashtag->save();

        $posts =
            Post::factory()
                ->count(21)
                ->for($author)
                ->create();

        foreach ($posts as $post) {
            $post
                ->hashtags()
                ->attach(
                    $hashtag->id
                );
        }

        $response =
            $this->getJson(
                '/api/hashtags/laravel/posts'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.posts'
            )
            ->assertJsonPath(
                'data.pagination.per_page',
                20
            )
            ->assertJsonPath(
                'data.pagination.has_more',
                true
            );

        $cursor =
            $response->json(
                'data.pagination.next_cursor'
            );

        $this->assertIsString(
            $cursor
        );

        $this->assertNotSame(
            '',
            $cursor
        );
    }
}
