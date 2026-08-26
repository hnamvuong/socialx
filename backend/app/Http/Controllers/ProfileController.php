<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(
        UpdateProfileRequest $request
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validated();

        $user->display_name = $validated['display_name'];
        $user->bio = $validated['bio'] ?? null;
        $user->location = $validated['location'] ?? null;
        $user->website = $validated['website'] ?? null;

        $user->save();

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
                    'cover_path' => $user->cover_path,
                    'is_private' => $user->is_private,
                    'is_verified' => $user->is_verified,
                    'created_at' => $user->created_at,
                ],
            ],
        ]);
    }
}
