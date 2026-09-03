<?php

namespace App\Http\Controllers;

use App\Models\FollowRequest;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function show(
        Request $request,
        string $username
    ): JsonResponse {
        $normalizedUsername = strtolower(trim($username));

        $user = User::query()
            ->where([
                'username' => $normalizedUsername,
                'status' => 'active',
            ])
            ->withCount([
                'followers',
                'following',
            ])
            ->firstOrFail();

        $viewer = $request->user('sanctum');

        $followState =
            $this->resolveFollowState(
                $viewer,
                $user
            );

        return response()->json([
            'data' => [
                'user' => [
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
                    'followers_count' => $user->followers_count,
                    'following_count' => $user->following_count,
                    'relationship' => $followState['relationship'],
                    'following' => $followState['following'],
                    'follow_requested' => $followState['follow_requested'],
                ],
            ],
        ]);
    }

    private function resolveFollowState(
        ?User $viewer,
        User $target
    ): array {
        if (! $viewer) {
            return [
                'relationship' => 'none',

                'following' => false,

                'follow_requested' => false,
            ];
        }

        if (
            $viewer->id ===
            $target->id
        ) {
            return [
                'relationship' => 'self',

                'following' => false,

                'follow_requested' => false,
            ];
        }

        $isFollowing =
            $viewer
                ->following()
                ->where(
                    'users.id',
                    $target->id
                )
                ->exists();

        if ($isFollowing) {
            return [
                'relationship' => 'following',

                'following' => true,

                'follow_requested' => false,
            ];
        }

        $hasPendingRequest =
            $viewer
                ->sentFollowRequests()
                ->where(
                    'target_id',
                    $target->id
                )
                ->where(
                    'status',
                    FollowRequest::STATUS_PENDING
                )
                ->exists();

        if ($hasPendingRequest) {
            return [
                'relationship' => 'requested',

                'following' => false,

                'follow_requested' => true,
            ];
        }

        return [
            'relationship' => 'none',

            'following' => false,

            'follow_requested' => false,
        ];
    }
}
