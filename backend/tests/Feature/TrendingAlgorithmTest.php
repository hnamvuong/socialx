<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendingAlgorithmTest extends TestCase
{
    use RefreshDatabase;

    private function createHashtag(
        string $name
    ): Hashtag {
        $hashtag =
            new Hashtag;

        $hashtag->name =
            $name;

        $hashtag->save();

        return $hashtag;
    }

    public function test_it_calculates_trending_metrics(): void
    {
        $userA =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $userB =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $hashtag =
            $this->createHashtag(
                'laravel'
            );

        $postA =
            Post::factory()
                ->for($userA)
                ->create([
                    'created_at' => now()
                        ->subMinutes(
                            30
                        ),
                ]);

        $postB =
            Post::factory()
                ->for($userB)
                ->create([
                    'created_at' => now()
                        ->subMinutes(
                            45
                        ),
                ]);

        $postC =
            Post::factory()
                ->for($userA)
                ->create([
                    'created_at' => now()
                        ->subHours(
                            3
                        ),
                ]);

        $hashtag
            ->posts()
            ->attach([
                $postA->id,
                $postB->id,
                $postC->id,
            ]);

        $response =
            $this->getJson(
                '/api/explore/trending'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.trends.0.name',
                'laravel'
            )
            ->assertJsonPath(
                'data.trends.0.posts_last_hour',
                2
            )
            ->assertJsonPath(
                'data.trends.0.posts_last_6_hours',
                3
            )
            ->assertJsonPath(
                'data.trends.0.unique_users',
                2
            )
            ->assertJsonPath(
                'data.trends.0.trend_score',
                22
            );
    }

    public function test_posts_older_than_six_hours_are_not_counted(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $hashtag =
            $this->createHashtag(
                'laravel'
            );

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'created_at' => now()
                        ->subHours(
                            7
                        ),
                ]);

        $hashtag
            ->posts()
            ->attach(
                $post->id
            );

        $this
            ->getJson(
                '/api/explore/trending'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.trends'
            );
    }

    public function test_private_user_posts_do_not_affect_public_trending(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => true,
                ]);

        $hashtag =
            $this->createHashtag(
                'secret'
            );

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'created_at' => now()
                        ->subMinutes(
                            10
                        ),
                ]);

        $hashtag
            ->posts()
            ->attach(
                $post->id
            );

        $this
            ->getJson(
                '/api/explore/trending'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.trends'
            );
    }

    public function test_inactive_user_posts_do_not_affect_trending(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'inactive',

                    'is_private' => false,
                ]);

        $hashtag =
            $this->createHashtag(
                'laravel'
            );

        $post =
            Post::factory()
                ->for($user)
                ->create([
                    'created_at' => now()
                        ->subMinutes(
                            10
                        ),
                ]);

        $hashtag
            ->posts()
            ->attach(
                $post->id
            );

        $this
            ->getJson(
                '/api/explore/trending'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.trends'
            );
    }

    public function test_hashtags_are_sorted_by_trend_score(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',

                    'is_private' => false,
                ]);

        $laravel =
            $this->createHashtag(
                'laravel'
            );

        $vue =
            $this->createHashtag(
                'vue'
            );

        $laravelPost =
            Post::factory()
                ->for($user)
                ->create([
                    'created_at' => now()
                        ->subMinutes(
                            10
                        ),
                ]);

        $laravel
            ->posts()
            ->attach(
                $laravelPost->id
            );

        for (
            $index = 0;
            $index < 3;
            $index++
        ) {
            $post =
                Post::factory()
                    ->for($user)
                    ->create([
                        'created_at' => now()
                            ->subMinutes(
                                10 + $index
                            ),
                    ]);

            $vue
                ->posts()
                ->attach(
                    $post->id
                );
        }

        $response =
            $this->getJson(
                '/api/explore/trending'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.trends.0.name',
                'vue'
            );
    }
}
