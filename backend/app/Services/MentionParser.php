<?php

namespace App\Services;

use Illuminate\Support\Str;

class MentionParser
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
            '/(?<![\p{L}\p{N}_])@([A-Za-z0-9_]+)/u',
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
                fn (string $mention): string => Str::lower(
                    $mention
                )
            )
            ->unique()
            ->values()
            ->all();
    }
}
