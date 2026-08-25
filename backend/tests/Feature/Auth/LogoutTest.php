<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $token = $user
            ->createToken('test-token')
            ->plainTextToken;

        $response = $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->postJson('/api/auth/logout');

        $response->assertOk();

        $this->assertDatabaseCount(
            'personal_access_tokens',
            0
        );
    }
}
