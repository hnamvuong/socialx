<?php

namespace App\Services;

use App\Models\Post;

class PostResponseService
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function toArray(
        Post $post,
        bool $likedByViewer = false,
        bool $repostedByViewer = false,
        bool $bookmarkedByViewer = false
    ): array {
        return [
            'id' => $post->id,

            'parent_post_id' => $post->parent_post_id,

            'root_post_id' => $post->root_post_id,

            'quoted_post_id' => $post->quoted_post_id,

            'content' => $post->content,

            'created_at' => $post->created_at,

            'updated_at' => $post->updated_at,

            'likes_count' => $post->likes_count
                ?? $post
                    ->likes()
                    ->count(),

            'liked_by_me' => $likedByViewer,

            'reposts_count' => $post->reposts_count
                ?? $post
                    ->reposts()
                    ->count(),

            'reposted_by_me' => $repostedByViewer,

            'bookmarked_by_me' => $bookmarkedByViewer,

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

            'quoted_post' => $this->quotedPostData(
                $post->quotedPost
            ),
        ];
    }

    private function quotedPostData(
        ?Post $post
    ): ?array {
        if (
            ! $post
            || ! $post->relationLoaded('user')
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
