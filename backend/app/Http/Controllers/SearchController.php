<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchHashtagRequest;
use App\Http\Requests\SearchPostRequest;
use App\Http\Requests\SearchUserRequest;
use App\Models\Bookmark;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Models\User;
use App\Services\PostResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __construct(
        private readonly PostResponseService $postResponse
    ) {}

    public function users(
        SearchUserRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        $query =
            Str::lower(
                $validated['q']
            );

        $like =
            '%'.$query.'%';

        $users =
            User::query()
                ->where(
                    'status',
                    'active'
                )
                ->where(
                    function ($builder) use (
                        $like
                    ): void {
                        $builder
                            ->whereRaw(
                                'LOWER(username) LIKE ?',
                                [$like]
                            )
                            ->orWhereRaw(
                                'LOWER(display_name) LIKE ?',
                                [$like]
                            );
                    }
                )
                ->orderByRaw(
                    'CASE
                        WHEN LOWER(username) = ? THEN 0
                        WHEN LOWER(username) LIKE ? THEN 1
                        ELSE 2
                    END',
                    [
                        $query,
                        $query.'%',
                    ]
                )
                ->orderBy(
                    'username'
                )
                ->limit(20)
                ->get([
                    'id',
                    'username',
                    'display_name',
                ]);

        return response()->json([
            'data' => [
                'users' => $users
                    ->map(
                        fn (User $user): array => [
                            'id' => $user->id,

                            'username' => $user->username,

                            'display_name' => $user->display_name,
                        ]
                    )
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function posts(
        SearchPostRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        $keyword =
            $validated['q'];

        $viewer =
            $request->user(
                'sanctum'
            );

        $postsQuery =
            Post::query()
                ->with([
                    'user',
                    'media',
                    'quotedPost.user',
                    'quotedPost.media',
                ])
                ->withCount([
                    'likes',
                    'reposts',
                ])
                ->whereNull(
                    'parent_post_id'
                )
                ->whereNotNull(
                    'content'
                )
                ->whereRaw(
                    'LOWER(content) LIKE ?',
                    [
                        '%'.
                        mb_strtolower(
                            $keyword
                        ).
                        '%',
                    ]
                )
                ->whereHas(
                    'user',
                    function ($query) use (
                        $viewer
                    ): void {
                        $query
                            ->where(
                                'status',
                                'active'
                            )
                            ->where(
                                function ($privacyQuery) use (
                                    $viewer
                                ): void {
                                    $privacyQuery
                                        ->where(
                                            'is_private',
                                            false
                                        );

                                    if (! $viewer) {
                                        return;
                                    }

                                    $privacyQuery
                                        ->orWhere(
                                            'users.id',
                                            $viewer->id
                                        )
                                        ->orWhereHas(
                                            'followers',
                                            function ($followQuery) use (
                                                $viewer
                                            ): void {
                                                $followQuery
                                                    ->where(
                                                        'users.id',
                                                        $viewer->id
                                                    );
                                            }
                                        );
                                }
                            );
                    }
                )
                ->orderByDesc(
                    'created_at'
                )
                ->orderByDesc(
                    'id'
                );

        $paginator =
            $postsQuery
                ->cursorPaginate(
                    20
                );

        $posts =
            collect(
                $paginator->items()
            );

        $likedPostIds =
            collect();

        $repostedPostIds =
            collect();

        $bookmarkedPostIds =
            collect();

        if (
            $viewer
            && $posts->isNotEmpty()
        ) {
            $postIds =
                $posts
                    ->pluck('id');

            $likedPostIds =
                Like::query()
                    ->where(
                        'user_id',
                        $viewer->id
                    )
                    ->whereIn(
                        'post_id',
                        $postIds
                    )
                    ->pluck(
                        'post_id'
                    )
                    ->flip();

            $repostedPostIds =
                Repost::query()
                    ->where(
                        'user_id',
                        $viewer->id
                    )
                    ->whereIn(
                        'post_id',
                        $postIds
                    )
                    ->pluck(
                        'post_id'
                    )
                    ->flip();

            $bookmarkedPostIds =
                Bookmark::query()
                    ->where(
                        'user_id',
                        $viewer->id
                    )
                    ->whereIn(
                        'post_id',
                        $postIds
                    )
                    ->pluck(
                        'post_id'
                    )
                    ->flip();
        }

        return response()->json([
            'data' => [
                'posts' => $posts
                    ->map(
                        fn (Post $post): array => $this
                            ->postResponse
                            ->toArray(
                                $post,
                                $likedPostIds
                                    ->has(
                                        $post->id
                                    ),
                                $repostedPostIds
                                    ->has(
                                        $post->id
                                    ),
                                $bookmarkedPostIds
                                    ->has(
                                        $post->id
                                    )
                            )
                    )
                    ->values()
                    ->all(),

                'pagination' => [
                    'per_page' => $paginator
                        ->perPage(),

                    'next_cursor' => $paginator
                        ->nextCursor()
                        ?->encode(),

                    'has_more' => $paginator
                        ->hasMorePages(),
                ],
            ],
        ]);
    }

    public function hashtags(
        SearchHashtagRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        $query =
            $validated['q'];

        $hashtags =
            Hashtag::query()
                ->where(
                    'name',
                    'like',
                    '%'.$query.'%'
                )
                ->orderByRaw(
                    'CASE
                    WHEN name = ? THEN 0
                    WHEN name LIKE ? THEN 1
                    ELSE 2
                END',
                    [
                        $query,
                        $query.'%',
                    ]
                )
                ->orderBy(
                    'name'
                )
                ->limit(20)
                ->get([
                    'id',
                    'name',
                ]);

        return response()->json([
            'data' => [
                'hashtags' => $hashtags
                    ->map(
                        fn (
                            Hashtag $hashtag
                        ): array => [
                            'id' => $hashtag->id,

                            'name' => $hashtag->name,
                        ]
                    )
                    ->values()
                    ->all(),
            ],
        ]);
    }
}
