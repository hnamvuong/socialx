<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchHashtagTest extends TestCase
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

    public function test_it_searches_hashtag_by_name(): void
    {
        $laravel =
            $this->createHashtag('laravel');

        $this->createHashtag('vue');

        $response =
            $this->getJson(
                '/api/search/hashtags?q=lara'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $laravel->id
            )
            ->assertJsonPath(
                'data.hashtags.0.name',
                'laravel'
            );
    }

    public function test_search_is_case_insensitive(): void
    {
        $hashtag =
            $this->createHashtag('laravel');

        $this
            ->getJson(
                '/api/search/hashtags?q=LARAVEL'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $hashtag->id
            );
    }

    public function test_search_accepts_hash_prefix(): void
    {
        $hashtag =
            $this->createHashtag('laravel');

        $this
            ->getJson(
                '/api/search/hashtags?q=%23laravel'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $hashtag->id
            );
    }

    public function test_it_supports_partial_match(): void
    {
        $hashtag =
            $this->createHashtag('programming');

        $this
            ->getJson(
                '/api/search/hashtags?q=gram'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $hashtag->id
            );
    }

    public function test_non_matching_hashtags_are_not_returned(): void
    {
        $this->createHashtag('laravel');

        $this
            ->getJson(
                '/api/search/hashtags?q=python'
            )
            ->assertOk()
            ->assertJsonCount(
                0,
                'data.hashtags'
            );
    }

    public function test_exact_match_is_ranked_before_prefix_match(): void
    {
        $this->createHashtag('laravel12');

        $exact =
            $this->createHashtag('laravel');

        $response =
            $this->getJson(
                '/api/search/hashtags?q=laravel'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $exact->id
            );
    }

    public function test_prefix_match_is_ranked_before_contains_match(): void
    {
        $contains =
            $this->createHashtag('php_laravel');

        $prefix =
            $this->createHashtag('laravel12');

        $response =
            $this->getJson(
                '/api/search/hashtags?q=laravel'
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $prefix->id
            );

        $this->assertNotSame(
            $contains->id,
            $response->json(
                'data.hashtags.0.id'
            )
        );
    }

    public function test_search_returns_at_most_twenty_hashtags(): void
    {
        for (
            $index = 1;
            $index <= 25;
            $index++
        ) {
            $this->createHashtag('socialx'.$index);
        }

        $response =
            $this->getJson(
                '/api/search/hashtags?q=socialx'
            );

        $response
            ->assertOk()
            ->assertJsonCount(
                20,
                'data.hashtags'
            );
    }

    public function test_query_is_required(): void
    {
        $this
            ->getJson(
                '/api/search/hashtags'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }

    public function test_blank_query_is_rejected(): void
    {
        $this
            ->getJson(
                '/api/search/hashtags?q=%20%20%20'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }

    public function test_hash_only_query_is_rejected(): void
    {
        $this
            ->getJson(
                '/api/search/hashtags?q=%23'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'q'
            );
    }

    public function test_it_searches_unicode_hashtag(): void
    {
        $hashtag =
            new Hashtag;

        $hashtag->name =
            'lậptrình';

        $hashtag->save();

        $response =
            $this->getJson(
                '/api/search/hashtags?q='
                .urlencode(
                    'lậptrình'
                )
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.hashtags.0.id',
                $hashtag->id
            );
    }
}
