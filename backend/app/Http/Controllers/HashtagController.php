<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Services\PostResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HashtagController extends Controller
{
    public function __construct(
        private readonly PostResponseService $postResponse
    ) {}

    public function posts(
        Request $request,
        string $hashtag
    ): JsonResponse {
        $hashtagName =
            Str::lower(
                trim($hashtag)
            );

        $hashtagModel =
            Hashtag::query()
                ->where(
                    'name',
                    $hashtagName
                )
                ->firstOrFail();

        $viewer =
            $request->user(
                'sanctum'
            );

        $viewerId =
            $viewer
                ? (int) $viewer->id
                : null;

        $query =
            $hashtagModel
                ->posts()
                ->whereNull(
                    'posts.parent_post_id'
                )
                ->whereHas(
                    'user',
                    function ($userQuery) use ($viewerId): void {
                        $userQuery
                            ->where(
                                'status',
                                'active'
                            )
                            ->where(
                                function ($privacyQuery) use ($viewerId): void {
                                    $privacyQuery->where(
                                        'is_private',
                                        false
                                    );

                                    if ($viewerId !== null) {
                                        $privacyQuery
                                            ->orWhereExists(
                                                function ($followQuery) use ($viewerId): void {
                                                    $followQuery
                                                        ->selectRaw('1')
                                                        ->from('follows')
                                                        ->whereColumn(
                                                            'follows.following_id',
                                                            'users.id'
                                                        )
                                                        ->where(
                                                            'follows.follower_id',
                                                            $viewerId
                                                        );
                                                }
                                            );
                                    }
                                }
                            );
                    }
                )
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
                ->select(
                    'posts.*'
                )
                ->orderByDesc(
                    'posts.created_at'
                )
                ->orderByDesc(
                    'posts.id'
                );

        $paginator =
            $query->cursorPaginate(
                perPage: 20
            );

        $posts =
            collect(
                $paginator->items()
            );

        $postIds =
            $posts->pluck(
                'id'
            );

        $likedPostIds =
            collect();

        $repostedPostIds =
            collect();

        $bookmarkedPostIds =
            collect();

        if ($viewerId !== null) {
            $likedPostIds =
                Like::query()
                    ->where(
                        'user_id',
                        $viewerId
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
                        $viewerId
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
                $viewer
                    ->bookmarks()
                    ->whereIn(
                        'post_id',
                        $postIds
                    )
                    ->pluck(
                        'post_id'
                    )
                    ->flip();
        }

        $serializedPosts =
            $posts
                ->map(
                    fn (Post $post): array => $this->postResponse
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
                ->all();

        return response()->json([
            'data' => [
                'hashtag' => [
                    'id' => $hashtagModel->id,

                    'name' => $hashtagModel->name,
                ],

                'posts' => $serializedPosts,

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
}
