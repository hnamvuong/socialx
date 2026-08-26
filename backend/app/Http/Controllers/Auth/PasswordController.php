<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function forgotPassword(
        ForgotPasswordRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        return response()->json([
            'message' => 'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $validated['password_confirmation'],
                'token' => $validated['token'],
            ],
            function (
                User $user,
                string $password
            ): void {
                $user->password = $password;

                $user->setRememberToken(
                    Str::random(60)
                );

                $user->save();

                $user->tokens()->delete();

                event(
                    new PasswordReset($user)
                );
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
            ], 422);
        }

        return response()->json([
            'message' => 'Đặt lại mật khẩu thành công.',
        ]);
    }
}
