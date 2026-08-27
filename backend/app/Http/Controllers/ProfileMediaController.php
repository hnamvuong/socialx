<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadAvatarRequest;
use App\Http\Requests\UploadCoverRequest;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProfileMediaController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function updateAvatar(
        UploadAvatarRequest $request
    ): JsonResponse {
        $user = $request->user();

        $oldPath = $user->avatar_path;

        $newPath =
            $this->storage->storePublicImage(
                $request->file('avatar'),
                'avatars'
            );

        try {
            $user->avatar_path = $newPath;
            $user->save();
        } catch (Throwable $exception) {
            $this->storage->deletePublic(
                $newPath
            );

            throw $exception;
        }

        $this->storage->deletePublic(
            $oldPath
        );

        return response()->json([
            'data' => [
                'user' => $this->profileData($user),
            ],
        ]);
    }

    public function updateCover(
        UploadCoverRequest $request
    ): JsonResponse {
        $user = $request->user();

        $oldPath = $user->cover_path;

        $newPath =
            $this->storage->storePublicImage(
                $request->file('cover'),
                'covers'
            );

        try {
            $user->cover_path = $newPath;
            $user->save();
        } catch (Throwable $exception) {
            $this->storage->deletePublic(
                $newPath
            );

            throw $exception;
        }

        $this->storage->deletePublic(
            $oldPath
        );

        return response()->json([
            'data' => [
                'user' => $this->profileData($user),
            ],
        ]);
    }

    private function profileData(
        object $user
    ): array {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'bio' => $user->bio,
            'location' => $user->location,
            'website' => $user->website,
            'avatar_path' => $user->avatar_path,
            'avatar_url' => $this->storage->publicUrl(
                $user->avatar_path
            ),
            'cover_path' => $user->cover_path,
            'cover_url' => $this->storage->publicUrl(
                $user->cover_path
            ),
            'is_private' => $user->is_private,
            'is_verified' => $user->is_verified,
            'created_at' => $user->created_at,
        ];
    }
}
