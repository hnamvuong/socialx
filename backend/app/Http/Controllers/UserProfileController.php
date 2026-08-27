<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function show(string $username): JsonResponse
    {
        $normalizedUsername = strtolower(trim($username));

        $user = User::query()
            ->where([
                'username' => $normalizedUsername,
                'status' => 'active',
            ])->firstOrFail();

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
                ],
            ],
        ]);
    }
}
