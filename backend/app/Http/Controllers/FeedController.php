<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Services\PostResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        private readonly PostResponseService $postResponse
    ) {}

    public function following(
        Request $request
    ): JsonResponse {
        $viewer =
            $request->user();

        $paginator =
            Post::query()
                ->join(
                    'follows',
                    function ($join) use ($viewer): void {
                        $join
                            ->on(
                                'follows.following_id',
                                '=',
                                'posts.user_id'
                            )
                            ->where(
                                'follows.follower_id',
                                '=',
                                $viewer->id
                            );
                    }
                )
                ->whereNull(
                    'posts.parent_post_id'
                )
                ->whereHas(
                    'user',
                    function ($query): void {
                        $query->where(
                            'status',
                            'active'
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
                ->orderByDesc(
                    'posts.created_at'
                )
                ->orderByDesc(
                    'posts.id'
                )
                ->select(
                    'posts.*'
                )
                ->cursorPaginate(
                    perPage: 1
                );

        $posts =
            collect(
                $paginator->items()
            );

        $postIds =
            $posts->pluck('id');

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

        $serializedPosts =
            $posts
                ->map(
                    fn (Post $post) => $this->postResponse
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
