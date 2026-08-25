<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['display_name'],
                'username' => strtolower($validated['username']),
                'display_name' => $validated['display_name'],
                'email' => strtolower($validated['email']),
                'password' => $validated['password'],
            ]);

            $user->settings()->create();

            $userRole = Role::query()
                ->where('name', 'user')
                ->first();

            if ($userRole === null) {
                throw new RuntimeException(
                    'Default user role has not been configured.'
                );
            }

            $user->roles()->attach($userRole->id);

            $user->refresh();

            return $user;
        });

        return response()->json([
            'message' => 'Đăng ký tài khoản thành công.',

            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'display_name' => $user->display_name,
                    'email' => $user->email,
                    'is_private' => $user->is_private,
                    'is_verified' => $user->is_verified,
                    'created_at' => $user->created_at,
                ],
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (
            $user === null ||
            ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ], 422);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Tài khoản hiện không thể đăng nhập.',
            ], 403);
        }

        $token = $user
            ->createToken('socialx-web')
            ->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công.',

            'data' => [
                'token' => $token,

                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'display_name' => $user->display_name,
                    'email' => $user->email,
                    'is_private' => $user->is_private,
                    'is_verified' => $user->is_verified,
                    'created_at' => $user->created_at,
                ],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();

        $user?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công.',
        ]);
    }
}
