<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(
        Request $request,
        int $id,
        string $hash
    ): JsonResponse {
        $user = User::query()->findOrFail($id);

        if (
            ! hash_equals(
                $hash,
                sha1(
                    $user->getEmailForVerification()
                )
            )
        ) {
            return response()->json([
                'message' => 'Liên kết xác minh email không hợp lệ.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email đã được xác minh trước đó.',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Xác minh email thành công.',
        ]);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email đã được xác minh.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Liên kết xác minh email đã được gửi.',
        ]);
    }
}
