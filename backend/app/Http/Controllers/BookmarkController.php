<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Services\PostResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly PostResponseService $postResponse
    ) {}

    public function index(
        Request $request
    ): JsonResponse {
        $user =
            $request->user();

        $paginator =
            Post::query()
                ->join(
                    'bookmarks',
                    function ($join) use ($user): void {
                        $join
                            ->on(
                                'bookmarks.post_id',
                                '=',
                                'posts.id'
                            )
                            ->where(
                                'bookmarks.user_id',
                                '=',
                                $user->id
                            );
                    }
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
                    'bookmarks.created_at'
                )
                ->orderByDesc(
                    'bookmarks.id'
                )
                ->select(
                    'posts.*'
                )
                ->paginate(
                    perPage: 20
                );

        $posts =
            $paginator
                ->getCollection();

        $postIds =
            $posts
                ->pluck('id');

        $likedPostIds =
            Like::query()
                ->where(
                    'user_id',
                    $user->id
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
                    $user->id
                )
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

                            true
                        )
                )
                ->values()
                ->all();

        return response()->json([
            'data' => [
                'posts' => $serializedPosts,

                'pagination' => [
                    'current_page' => $paginator
                        ->currentPage(),

                    'last_page' => $paginator
                        ->lastPage(),

                    'per_page' => $paginator
                        ->perPage(),

                    'total' => $paginator
                        ->total(),

                    'has_more' => $paginator
                        ->hasMorePages(),
                ],
            ],
        ]);
    }
}
