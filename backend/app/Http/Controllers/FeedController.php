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
                    perPage: 20
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

    public function forYou(
        Request $request
    ): JsonResponse {
        $viewer =
            $request->user();

        $viewerId =
            (int) $viewer->id;

        $perPage = 20;

        /*
         * ------------------------------------------------------------
         * 1. Những user mà viewer đang follow
         * ------------------------------------------------------------
         */
        $followedUserIds =
            $viewer
                ->following()
                ->pluck('users.id')
                ->map(
                    fn ($id): int => (int) $id
                )
                ->all();

        /*
         * ------------------------------------------------------------
         * 2. Author mà viewer từng tương tác
         *
         * Interest hiện tại được suy ra từ:
         * - Like
         * - Repost
         * - Bookmark
         * ------------------------------------------------------------
         */
        $interactedAuthorIds =
            Post::query()
                ->where(
                    function ($query) use ($viewerId): void {
                        $query
                            ->whereIn(
                                'posts.id',
                                function ($subQuery) use ($viewerId): void {
                                    $subQuery
                                        ->select(
                                            'post_id'
                                        )
                                        ->from(
                                            'likes'
                                        )
                                        ->where(
                                            'user_id',
                                            $viewerId
                                        );
                                }
                            )
                            ->orWhereIn(
                                'posts.id',
                                function ($subQuery) use ($viewerId): void {
                                    $subQuery
                                        ->select(
                                            'post_id'
                                        )
                                        ->from(
                                            'reposts'
                                        )
                                        ->where(
                                            'user_id',
                                            $viewerId
                                        );
                                }
                            )
                            ->orWhereIn(
                                'posts.id',
                                function ($subQuery) use ($viewerId): void {
                                    $subQuery
                                        ->select(
                                            'post_id'
                                        )
                                        ->from(
                                            'bookmarks'
                                        )
                                        ->where(
                                            'user_id',
                                            $viewerId
                                        );
                                }
                            );
                    }
                )
                ->pluck(
                    'user_id'
                )
                ->map(
                    fn ($id): int => (int) $id
                )
                ->unique()
                ->all();

        /*
         * ------------------------------------------------------------
         * 3. Candidate posts
         * ------------------------------------------------------------
         *
         * Bài 48:
         * - chỉ top-level post
         * - không recommend post của chính viewer
         * - author phải active
         * - public user được recommend
         * - private user chỉ được thấy nếu viewer đã follow
         *
         * Limit 200 là candidate pool tạm thời.
         * ------------------------------------------------------------
         */
        $candidateQuery =
            Post::query()
                ->whereNull(
                    'posts.parent_post_id'
                )
                ->where(
                    'posts.user_id',
                    '!=',
                    $viewerId
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
                ->where(
                    function ($query) use ($followedUserIds): void {
                        $query
                            ->whereHas(
                                'user',
                                function ($userQuery): void {
                                    $userQuery->where(
                                        'is_private',
                                        false
                                    );
                                }
                            );

                        if (
                            count(
                                $followedUserIds
                            ) > 0
                        ) {
                            $query->orWhereIn(
                                'posts.user_id',
                                $followedUserIds
                            );
                        }
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
                ->limit(200);

        $candidates =
            $candidateQuery->get();

        /*
         * ------------------------------------------------------------
         * 4. Ranking
         * ------------------------------------------------------------
         *
         * score =
         * recency      * 0.35
         * engagement   * 0.30
         * relationship * 0.20
         * interest     * 0.15
         * ------------------------------------------------------------
         */
        $rankedPosts =
            $candidates
                ->map(
                    function (Post $post) use (
                        $followedUserIds,
                        $interactedAuthorIds
                    ): array {
                        /*
                         * Recency
                         *
                         * 0 giờ   -> 1
                         * 84 giờ  -> 0.5
                         * 168 giờ -> 0
                         */
                        $ageHours =
                            max(
                                0,
                                $post
                                    ->created_at
                                    ->diffInHours(
                                        now()
                                    )
                            );

                        $recencyScore =
                            max(
                                0,
                                min(
                                    1,
                                    1 - (
                                        $ageHours / 168
                                    )
                                )
                            );

                        /*
                         * Engagement
                         *
                         * Like   = 1 point
                         * Repost = 2 points
                         *
                         * 50 điểm trở lên
                         * được normalize thành 1.
                         */
                        $engagementPoints =
                            (int) $post->likes_count
                            +
                            (
                                (int) $post->reposts_count
                                * 2
                            );

                        $engagementScore =
                            min(
                                1,
                                $engagementPoints / 50
                            );

                        /*
                         * Relationship
                         */
                        $relationshipScore =
                            in_array(
                                (int) $post->user_id,
                                $followedUserIds,
                                true
                            )
                                ? 1
                                : 0;

                        /*
                         * Interest
                         */
                        $interestScore =
                            in_array(
                                (int) $post->user_id,
                                $interactedAuthorIds,
                                true
                            )
                                ? 1
                                : 0;

                        $rankingScore =
                            (
                                $recencyScore
                                * 0.35
                            )
                            +
                            (
                                $engagementScore
                                * 0.30
                            )
                            +
                            (
                                $relationshipScore
                                * 0.20
                            )
                            +
                            (
                                $interestScore
                                * 0.15
                            );

                        return [
                            'post' => $post,

                            'score' => $rankingScore,
                        ];
                    }
                )
                ->sort(
                    function (
                        array $left,
                        array $right
                    ): int {
                        /*
                         * Score cao hơn lên trước.
                         */
                        $scoreCompare =
                            $right['score']
                            <=>
                            $left['score'];

                        if (
                            $scoreCompare !== 0
                        ) {
                            return $scoreCompare;
                        }

                        /*
                         * Nếu score bằng nhau:
                         * Post ID lớn hơn lên trước.
                         */
                        return
                            $right['post']->id
                            <=>
                            $left['post']->id;
                    }
                )
                ->values();

        /*
         * ------------------------------------------------------------
         * 5. Decode cursor
         * ------------------------------------------------------------
         *
         * Cursor chứa ID của post cuối
         * trong batch trước.
         *
         * Frontend coi cursor là opaque string.
         * ------------------------------------------------------------
         */
        $cursor =
            $request->query(
                'cursor'
            );

        $startIndex = 0;

        if (
            is_string($cursor)
            && $cursor !== ''
        ) {
            $decodedCursor =
                base64_decode(
                    $cursor,
                    true
                );

            abort_if(
                $decodedCursor === false
                || ! ctype_digit(
                    $decodedCursor
                ),
                422,
                'Cursor không hợp lệ.'
            );

            $cursorPostId =
                (int) $decodedCursor;

            $cursorIndex =
                $rankedPosts
                    ->search(
                        function (
                            array $item
                        ) use ($cursorPostId): bool {
                            return
                                (int) $item['post']->id
                                === $cursorPostId;
                        }
                    );

            abort_if(
                $cursorIndex === false,
                422,
                'Cursor không còn hợp lệ.'
            );

            $startIndex =
                $cursorIndex + 1;
        }

        /*
         * ------------------------------------------------------------
         * 6. Lấy batch hiện tại
         * ------------------------------------------------------------
         */
        $pageItems =
            $rankedPosts
                ->slice(
                    $startIndex,
                    $perPage
                )
                ->values();

        $posts =
            $pageItems
                ->pluck(
                    'post'
                )
                ->values();

        $hasMore =
            (
                $startIndex
                + $pageItems->count()
            )
            < $rankedPosts->count();

        /*
         * Cursor tiếp theo = ID của post cuối
         * trong batch hiện tại.
         */
        $nextCursor =
            null;

        if (
            $hasMore
            && $posts->isNotEmpty()
        ) {
            /** @var Post|null $lastPost */
            $lastPost =
                $posts->last();

            if ($lastPost) {
                $nextCursor =
                    base64_encode(
                        (string) $lastPost->id
                    );
            }
        }

        /*
         * ------------------------------------------------------------
         * 7. Viewer interaction states
         * ------------------------------------------------------------
         */
        $postIds =
            $posts->pluck(
                'id'
            );

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

        /*
         * ------------------------------------------------------------
         * 8. Serialize theo PostResponseService hiện tại
         * ------------------------------------------------------------
         */
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

        /*
         * ------------------------------------------------------------
         * 9. Response
         * ------------------------------------------------------------
         */
        return response()->json([
            'data' => [
                'posts' => $serializedPosts,

                'pagination' => [
                    'per_page' => $perPage,

                    'next_cursor' => $nextCursor,

                    'has_more' => $hasMore,
                ],
            ],
        ]);
    }
}
