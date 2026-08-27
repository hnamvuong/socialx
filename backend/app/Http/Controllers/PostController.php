<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
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

        $storedPaths = [];

        try {
            $post = DB::transaction(
                function () use (
                    $request,
                    $user,
                    $validated,
                    &$storedPaths
                ): Post {
                    $post = $user
                        ->posts()
                        ->create([
                            'content' => $validated[
                                    'content'
                                ] ?? null,
                        ]);

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
        ]);

        return response()->json(
            [
                'data' => [
                    'post' => [
                        'id' => $post->id,
                        'content' => $post->content,
                        'created_at' => $post->created_at,
                        'updated_at' => $post->updated_at,
                        'user' => [
                            'id' => $post->user->id,
                            'username' => $post
                                ->user
                                ->username,
                            'display_name' => $post
                                ->user
                                ->display_name,
                            'avatar_url' => $this->storage->publicUrl(
                                $post->user->avatar_path
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
                                                $media
                                                    ->path
                                            ),
                                        'mime_type' => $media->mime_type,
                                        'width' => $media->width,
                                        'height' => $media->height,
                                        'sort_order' => $media->sort_order,
                                    ];
                                }
                            )
                            ->values(),
                    ],
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
        ]);

        return response()->json([
            'data' => [
                'post' => [
                    'id' => $post->id,
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
                        ->values(),
                ],
            ],
        ]);
    }
}
