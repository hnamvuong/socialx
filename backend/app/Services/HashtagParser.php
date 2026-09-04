<?php

namespace App\Services;

use Illuminate\Support\Str;

class HashtagParser
{
    /**
     * @return array<int, string>
     */
    public function parse(
        string $content
    ): array {
        if ($content === '') {
            return [];
        }

        preg_match_all(
            '/(?<![\p{L}\p{N}_])#([\p{L}\p{N}_]+)/u',
            $content,
            $matches
        );

        if (
            ! isset($matches[1])
            || $matches[1] === []
        ) {
            return [];
        }

        return collect(
            $matches[1]
        )
            ->map(
                fn (string $hashtag): string => Str::lower(
                    $hashtag
                )
            )
            ->unique()
            ->values()
            ->all();
    }
}
