<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\CreateReplyRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Like;
use App\Models\Post;
use App\Models\Repost;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function store(
        CreatePostRequest $request
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validated();

        $quotedPost = null;

        if (
            isset(
                $validated[
                    'quoted_post_id'
                ]
            )
        ) {
            $quotedPost =
                Post::query()
                    ->with('user')
                    ->findOrFail(
                        $validated[
                            'quoted_post_id'
                        ]
                    );

            abort_if(
                $quotedPost
                    ->user
                    ->status !== 'active',
                404
            );
        }

        $storedPaths = [];

        try {
            $post = DB::transaction(
                function () use (
                    $request,
                    $user,
                    $validated,
                    &$storedPaths,
                    $quotedPost
                ): Post {
                    $post = $user
                        ->posts()
                        ->create([
                            'content' => $validated[
                                    'content'
                                ] ?? null,
                        ]);

                    if ($quotedPost) {
                        $post->quoted_post_id =
                            $quotedPost->id;

                        $post->save();
                    }

                    $files =
                        $request->file(
                            'media',
                            []
                        );

                    foreach (
                        $files as $index => $file
                    ) {
                        $path =
                            $this->storage
                                ->storePublicImage(
                                    $file,
                                    "posts/{$post->id}"
                                );

                        $storedPaths[] =
                            $path;

                        $dimensions =
                            getimagesize(
                                $file
                                    ->getRealPath()
                            );

                        $width =
                            is_array(
                                $dimensions
                            )
                                ? $dimensions[0]
                                : null;

                        $height =
                            is_array(
                                $dimensions
                            )
                                ? $dimensions[1]
                                : null;

                        $post
                            ->media()
                            ->create([
                                'type' => 'image',
                                'path' => $path,
                                'mime_type' => $file
                                    ->getMimeType(),
                                'width' => $width,
                                'height' => $height,
                                'sort_order' => $index,
                            ]);
                    }

                    return $post;
                }
            );
        } catch (Throwable $exception) {
            foreach (
                $storedPaths as $path
            ) {
                $this->storage
                    ->deletePublic(
                        $path
                    );
            }

            throw $exception;
        }

        $post->load([
            'user',
            'media',

            'quotedPost.user',
            'quotedPost.media',
        ]);

        $post->loadCount(
            'likes',
            'reposts'
        );

        return response()->json(
            [
                'data' => [
                    'post' => $this->postData(
                        $post,
                        false,
                        false
                    ),
                ],
            ],
            201
        );
    }

    public function update(
        UpdatePostRequest $request,
        Post $post
    ): JsonResponse {
        $validated = $request->validated();

        $post->content = $validated['content'] ?? null;

        $post->save();

        $post->load([
            'user',
            'media',

            'quotedPost.user',
            'quotedPost.media',
        ]);

        $post->loadCount(
            'likes'
        );

        $viewer = $request->user();

        $likedByViewer =
            $post
                ->likes()
                ->where(
                    'user_id',
                    $viewer->id
                )
                ->exists();

        $repostedByViewer =
            $post
                ->reposts()
                ->where(
                    'user_id',
                    $viewer->id
                )
                ->exists();

        return response()->json([
            'data' => [
                'post' => $this->postData(
                    $post,
                    $likedByViewer,
                    $repostedByViewer
                ),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        Post $post
    ): Response {
        $user = $request->user();

        abort_unless(
            $user &&
            $user->id === $post->user_id,
            403
        );

        $post->load('media');

        $mediaPaths = $post
            ->media
            ->pluck('path')
            ->filter()
            ->values()
            ->all();

        $post->delete();

        foreach ($mediaPaths as $path) {
            $this->storage
                ->deletePublic($path);
        }

        return response()->noContent();
    }

    private function postData(
        Post $post,
        bool $likedByViewer = false,
        bool $repostedByViewer = false
    ): array {
        return [
            'id' => $post->id,
            'parent_post_id' => $post->parent_post_id,
            'root_post_id' => $post->root_post_id,
            'quoted_post_id' => $post->quoted_post_id,
            'content' => $post->content,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'user' => [
                'id' => $post->user->id,
                'username' => $post->user->username,
                'display_name' => $post->user->display_name,
                'avatar_url' => $this->storage
                    ->publicUrl(
                        $post
                            ->user
                            ->avatar_path
                    ),
                'is_verified' => $post
                    ->user
                    ->is_verified,
            ],
            'media' => $post
                ->media
                ->map(
                    function (
                        $media
                    ): array {
                        return [
                            'id' => $media->id,
                            'type' => $media->type,
                            'path' => $media->path,
                            'url' => $this
                                ->storage
                                ->publicUrl(
                                    $media->path
                                ),
                            'mime_type' => $media->mime_type,
                            'width' => $media->width,
                            'height' => $media->height,
                            'sort_order' => $media->sort_order,
                        ];
                    }
                )
                ->values()
                ->all(),
            'likes_count' => $post->likes_count ?? $post->likes()->count(),
            'liked_by_me' => $likedByViewer,
            'reposts_count' => $post->reposts_count ?? $post->reposts()->count(),
            'reposted_by_me' => $repostedByViewer,
            'quoted_post' => $this->quotedPostData(
                $post->quotedPost
            ),
        ];
    }

    public function show(
        Request $request,
        Post $post
    ): JsonResponse {
        $post->load([
            'user',
            'media',

            'quotedPost.user',
            'quotedPost.media',
        ]);

        $post->loadCount([
            'likes',
            'reposts',
        ]);

        $viewer = $request->user('sanctum');

        $likedByViewer = false;
        $repostedByViewer = false;

        if ($viewer) {
            $likedByViewer =
                $post
                    ->likes()
                    ->where(
                        'user_id',
                        $viewer->id
                    )
                    ->exists();

            $repostedByViewer =
                $post
                    ->reposts()
                    ->where(
                        'user_id',
                        $viewer->id
                    )
                    ->exists();
        }

        abort_if(
            $post->user->status !== 'active',
            404
        );

        return response()->json([
            'data' => [
                'post' => $this->postData(
                    $post,
                    $likedByViewer,
                    $repostedByViewer
                ),
            ],
        ]);
    }

    public function reply(
        CreateReplyRequest $request,
        Post $post
    ): JsonResponse {
        $user = $request->user();

        abort_if(
            $post->user->status !== 'active',
            404
        );

        $validated = $request->validated();

        $storedPaths = [];

        try {
            $reply = DB::transaction(
                function () use (
                    $request,
                    $user,
                    $post,
                    $validated,
                    &$storedPaths
                ): Post {
                    $reply =
                        $user
                            ->posts()
                            ->create([
                                'content' => $validated[
                                        'content'
                                    ] ?? null,
                            ]);

                    $reply->parent_post_id = $post->id;

                    $reply->root_post_id = $post->root_post_id ?? $post->id;

                    $reply->save();

                    $files = $request->file(
                        'media',
                        []
                    );

                    foreach (
                        $files as $index => $file
                    ) {
                        $path =
                            $this->storage
                                ->storePublicImage(
                                    $file,
                                    "posts/{$reply->id}"
                                );

                        $storedPaths[] =
                            $path;

                        $dimensions =
                            getimagesize(
                                $file->getRealPath()
                            );

                        $width =
                            is_array($dimensions)
                                ? $dimensions[0]
                                : null;

                        $height =
                            is_array($dimensions)
                                ? $dimensions[1]
                                : null;

                        $reply
                            ->media()
                            ->create([
                                'type' => 'image',

                                'path' => $path,

                                'mime_type' => $file
                                    ->getMimeType(),

                                'width' => $width,

                                'height' => $height,

                                'sort_order' => $index,
                            ]);
                    }

                    return $reply;
                }
            );
        } catch (Throwable $exception) {
            foreach (
                $storedPaths as $path
            ) {
                $this->storage
                    ->deletePublic(
                        $path
                    );
            }

            throw $exception;
        }

        $reply->load([
            'user',
            'media',
        ]);

        $reply->loadCount(
            'likes',
            'reposts'
        );

        return response()->json(
            [
                'data' => [
                    'post' => $this->postData(
                        $reply,
                        false,
                        false
                    ),
                ],
            ],
            201
        );
    }

    public function thread(
        Request $request,
        Post $post
    ): JsonResponse {
        $rootId =
            $post->root_post_id
            ?? $post->id;

        $root =
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
                ->findOrFail(
                    $rootId
                );

        abort_if(
            $root->user->status !== 'active',
            404
        );

        $replies =
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
                ->where(
                    'root_post_id',
                    $root->id
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
                ->orderBy(
                    'created_at'
                )
                ->orderBy(
                    'id'
                )
                ->get();

        $viewer = $request->user('sanctum');

        $postIds =
            $replies
                ->pluck('id')
                ->prepend(
                    $root->id
                )
                ->values();

        $likedPostIds = collect();

        $repostedPostIds = collect();

        if ($viewer) {
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
        }

        return response()->json([
            'data' => [
                'root' => $this->postData(
                    $root,
                    $likedPostIds->has(
                        $root->id
                    ),
                    $repostedPostIds->has(
                        $root->id
                    )
                ),

                'replies' => $replies
                    ->map(
                        fn (Post $reply) => $this->postData(
                            $reply,
                            $likedPostIds->has(
                                $reply->id
                            ),
                            $repostedPostIds->has(
                                $reply->id
                            )
                        )
                    )
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function like(
        Request $request,
        Post $post
    ): JsonResponse {
        $user = $request->user();

        abort_if(
            $post->user->status !== 'active',
            404
        );

        $existingLike =
            Like::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'post_id',
                    $post->id
                )
                ->first();

        if (! $existingLike) {
            $like = new Like;

            $like->user_id = $user->id;
            $like->post_id = $post->id;

            $like->save();
        }

        return response()->json([
            'data' => [
                'liked' => true,
                'likes_count' => $post
                    ->likes()
                    ->count(),
            ],
        ]);
    }

    public function unlike(
        Request $request,
        Post $post
    ): JsonResponse {
        $user = $request->user();

        abort_if(
            $post->user->status !== 'active',
            404
        );

        Like::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'post_id',
                $post->id
            )
            ->delete();

        return response()->json([
            'data' => [
                'liked' => false,

                'likes_count' => $post
                    ->likes()
                    ->count(),
            ],
        ]);
    }

    public function repost(
        Request $request,
        Post $post
    ): JsonResponse {
        $user =
            $request->user();

        abort_if(
            $post->user->status !== 'active',
            404
        );

        $existingRepost =
            Repost::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'post_id',
                    $post->id
                )
                ->first();

        if (! $existingRepost) {
            $repost = new Repost;

            $repost->user_id = $user->id;

            $repost->post_id = $post->id;

            $repost->save();
        }

        return response()->json([
            'data' => [
                'reposted' => true,

                'reposts_count' => $post
                    ->reposts()
                    ->count(),
            ],
        ]);
    }

    public function unrepost(
        Request $request,
        Post $post
    ): JsonResponse {
        $user =
            $request->user();

        abort_if(
            $post->user->status !== 'active',
            404
        );

        Repost::query()
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'post_id',
                $post->id
            )
            ->delete();

        return response()->json([
            'data' => [
                'reposted' => false,

                'reposts_count' => $post
                    ->reposts()
                    ->count(),
            ],
        ]);
    }

    private function quotedPostData(
        ?Post $post
    ): ?array {
        if (! $post) {
            return null;
        }

        if (
            ! $post->relationLoaded(
                'user'
            )
            || $post->user->status !== 'active'
        ) {
            return null;
        }

        return [
            'id' => $post->id,

            'content' => $post->content,

            'created_at' => $post->created_at,

            'user' => [
                'id' => $post->user->id,

                'username' => $post->user->username,

                'display_name' => $post->user->display_name
                    ?? $post->user->name,

                'avatar_url' => $post->user->avatar_path
                        ? $this->storage
                            ->publicUrl(
                                $post->user->avatar_path
                            )
                        : null,

                'is_verified' => (bool)
                    $post->user->is_verified,
            ],

            'media' => $post
                ->media
                ->map(
                    fn ($media) => [
                        'id' => $media->id,

                        'type' => $media->type,

                        'url' => $this->storage
                            ->publicUrl(
                                $media->path
                            ),

                        'width' => $media->width,

                        'height' => $media->height,

                        'sort_order' => $media->sort_order,
                    ]
                )
                ->values()
                ->all(),
        ];
    }
}
