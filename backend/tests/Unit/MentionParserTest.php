<?php

namespace Tests\Unit;

use App\Services\MentionParser;
use Tests\TestCase;

class MentionParserTest extends TestCase
{
    private MentionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser =
            new MentionParser;
    }

    public function test_it_parses_single_mention(): void
    {
        $result =
            $this->parser->parse(
                'Xin chào @alice'
            );

        $this->assertSame(
            [
                'alice',
            ],
            $result
        );
    }

    public function test_it_parses_multiple_mentions(): void
    {
        $result =
            $this->parser->parse(
                'Xin chào @alice, @bob và @user_123'
            );

        $this->assertSame(
            [
                'alice',
                'bob',
                'user_123',
            ],
            $result
        );
    }

    public function test_it_normalizes_mentions_to_lowercase(): void
    {
        $result =
            $this->parser->parse(
                '@Alice @BOB'
            );

        $this->assertSame(
            [
                'alice',
                'bob',
            ],
            $result
        );
    }

    public function test_it_removes_duplicate_mentions(): void
    {
        $result =
            $this->parser->parse(
                '@Alice @alice @ALICE'
            );

        $this->assertSame(
            [
                'alice',
            ],
            $result
        );
    }

    public function test_mention_stops_at_punctuation(): void
    {
        $result =
            $this->parser->parse(
                '@alice, @bob! @charlie.'
            );

        $this->assertSame(
            [
                'alice',
                'bob',
                'charlie',
            ],
            $result
        );
    }

    public function test_it_does_not_parse_email_address_as_mention(): void
    {
        $result =
            $this->parser->parse(
                'Email: alice@example.com'
            );

        $this->assertSame(
            [],
            $result
        );
    }

    public function test_it_does_not_match_at_sign_inside_word(): void
    {
        $result =
            $this->parser->parse(
                'hello@alice'
            );

        $this->assertSame(
            [],
            $result
        );
    }

    public function test_it_ignores_at_sign_without_username(): void
    {
        $result =
            $this->parser->parse(
                'Hello @ và @!'
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
