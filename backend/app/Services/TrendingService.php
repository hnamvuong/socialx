<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrendingService
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     posts_last_hour: int,
     *     posts_last_6_hours: int,
     *     unique_users: int,
     *     trend_score: int
     * }>
     */
    public function hashtags(
        int $limit = 10
    ): Collection {
        $now = now();

        $oneHourAgo =
            $now->copy()
                ->subHour();

        $sixHoursAgo =
            $now->copy()
                ->subHours(6);

        $rows =
            DB::table('hashtags')
                ->join(
                    'post_hashtags',
                    'post_hashtags.hashtag_id',
                    '=',
                    'hashtags.id'
                )
                ->join(
                    'posts',
                    'posts.id',
                    '=',
                    'post_hashtags.post_id'
                )
                ->join(
                    'users',
                    'users.id',
                    '=',
                    'posts.user_id'
                )
                ->where(
                    'users.status',
                    'active'
                )
                ->where(
                    'users.is_private',
                    false
                )
                ->where(
                    'posts.created_at',
                    '>=',
                    $sixHoursAgo
                )
                ->groupBy(
                    'hashtags.id',
                    'hashtags.name'
                )
                ->select([
                    'hashtags.id',
                    'hashtags.name',
                ])
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN posts.created_at >= ?
                            THEN 1
                            ELSE 0
                        END
                    ) AS posts_last_hour
                    ',
                    [
                        $oneHourAgo,
                    ]
                )
                ->selectRaw(
                    '
                    COUNT(posts.id)
                    AS posts_last_6_hours
                    '
                )
                ->selectRaw(
                    '
                    COUNT(
                        DISTINCT posts.user_id
                    )
                    AS unique_users
                    '
                )
                ->get();

        return $rows
            ->map(
                function ($row): array {
                    $postsLastHour =
                        (int)
                        $row
                            ->posts_last_hour;

                    $postsLast6Hours =
                        (int)
                        $row
                            ->posts_last_6_hours;

                    $uniqueUsers =
                        (int)
                        $row
                            ->unique_users;

                    $trendScore =
                        (
                            $postsLastHour
                            * 5
                        )
                        +
                        (
                            $postsLast6Hours
                            * 2
                        )
                        +
                        (
                            $uniqueUsers
                            * 3
                        );

                    return [
                        'id' => (int)
                            $row->id,

                        'name' => $row->name,

                        'posts_last_hour' => $postsLastHour,

                        'posts_last_6_hours' => $postsLast6Hours,

                        'unique_users' => $uniqueUsers,

                        'trend_score' => $trendScore,
                    ];
                }
            )
            ->sortByDesc(
                'trend_score'
            )
            ->take(
                $limit
            )
            ->values();
    }
}
