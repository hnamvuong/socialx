<?php

namespace Tests\Unit;

use App\Services\HashtagParser;
use Tests\TestCase;

class HashtagParserTest extends TestCase
{
    private HashtagParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser =
            new HashtagParser;
    }

    public function test_it_parses_single_hashtag(): void
    {
        $result =
            $this->parser->parse(
                'Tôi thích #programming'
            );

        $this->assertSame(
            [
                'programming',
            ],
            $result
        );
    }

    public function test_it_parses_multiple_hashtags(): void
    {
        $result =
            $this->parser->parse(
                'Học #Laravel #Vue3 và #MySQL'
            );

        $this->assertSame(
            [
                'laravel',
                'vue3',
                'mysql',
            ],
            $result
        );
    }

    public function test_it_removes_duplicate_hashtags(): void
    {
        $result =
            $this->parser->parse(
                '#Laravel #laravel #LARAVEL'
            );

        $this->assertSame(
            [
                'laravel',
            ],
            $result
        );
    }

    public function test_it_supports_unicode_hashtags(): void
    {
        $result =
            $this->parser->parse(
                'Học #lậptrình và #日本語'
            );

        $this->assertSame(
            [
                'lậptrình',
                '日本語',
            ],
            $result
        );
    }

    public function test_hashtag_stops_at_punctuation(): void
    {
        $result =
            $this->parser->parse(
                'Học #laravel, #vue.js và #php!'
            );

        $this->assertSame(
            [
                'laravel',
                'vue',
                'php',
            ],
            $result
        );
    }

    public function test_it_does_not_match_hash_inside_a_word(): void
    {
        $result =
            $this->parser->parse(
                'hello#world'
            );

        $this->assertSame(
            [],
            $result
        );
    }

    public function test_it_ignores_hash_without_hashtag_content(): void
    {
        $result =
            $this->parser->parse(
                'Hello # world #'
            );

        $this->assertSame(
            [],
            $result
        );
    }

    public function test_empty_content_returns_empty_array(): void
    {
        $result =
            $this->parser->parse(
                ''
            );

        $this->assertSame(
            [],
            $result
        );
    }
}
