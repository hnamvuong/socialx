<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson(
            '/api/auth/forgot-password',
            [
                'email' => $user->email,
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
            ]);
    }

    public function test_non_existing_email_gets_same_response(): void
    {
        Notification::fake();

        $response = $this->postJson(
            '/api/auth/forgot-password',
            [
                'email' => 'nobody@example.com',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Nếu email tồn tại trong hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi.',
            ]);
    }

    public function test_email_must_be_valid(): void
    {
        $this->postJson(
            '/api/auth/forgot-password',
            [
                'email' => 'invalid-email',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }
}
