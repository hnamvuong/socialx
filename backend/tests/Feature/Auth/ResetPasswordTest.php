<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_reset(): void
    {
        Event::fake([
            PasswordReset::class,
        ]);

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson(
            '/api/auth/reset-password',
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Đặt lại mật khẩu thành công.',
            ]);

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'newpassword123',
                $user->password
            )
        );

        Event::assertDispatched(
            PasswordReset::class
        );
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(
            '/api/auth/reset-password',
            [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]
        );

        $response->assertUnprocessable();

        $user->refresh();

        $this->assertTrue(
            Hash::check(
                'password123',
                $user->password
            )
        );
    }

    public function test_reset_token_cannot_be_used_twice(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $token = Password::createToken($user);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $this
            ->postJson(
                '/api/auth/reset-password',
                $payload
            )
            ->assertOk();

        $this
            ->postJson(
                '/api/auth/reset-password',
                $payload
            )
            ->assertUnprocessable();
    }

    public function test_api_tokens_are_revoked_after_password_reset(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $user->createToken('device-a');
        $user->createToken('device-b');

        $this->assertDatabaseCount(
            'personal_access_tokens',
            2
        );

        $token = Password::createToken($user);

        $this->postJson(
            '/api/auth/reset-password',
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]
        )->assertOk();

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }
}
