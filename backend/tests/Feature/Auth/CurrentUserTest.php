<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_me(): void
    {
        $this
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_get_current_user(): void
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
            ->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.user.username',
                'alice'
            );
    }

    public function test_revoked_token_cannot_access_me(): void
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

        $user->tokens()->delete();

        $this
            ->withHeader(
                'Authorization',
                'Bearer '.$token
            )
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }
}
