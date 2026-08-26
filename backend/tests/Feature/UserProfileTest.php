<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_profile_can_be_viewed(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'bio' => 'Hello SocialX',
            'location' => 'Tokyo',
            'website' => 'https://example.com',
        ]);

        $response = $this->getJson(
            '/api/users/alice'
        );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => 'alice',
                        'display_name' => 'Alice',
                        'bio' => 'Hello SocialX',
                        'location' => 'Tokyo',
                        'website' => 'https://example.com',
                    ],
                ],
            ]);
    }

    public function test_public_profile_does_not_expose_email(): void
    {
        User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $this
            ->getJson('/api/users/alice')
            ->assertOk()
            ->assertJsonMissing([
                'email' => 'alice@example.com',
            ]);
    }

    public function test_username_lookup_is_case_insensitive(): void
    {
        User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $this
            ->getJson('/api/users/ALICE')
            ->assertOk()
            ->assertJsonPath(
                'data.user.username',
                'alice'
            );
    }

    public function test_unknown_profile_returns_404(): void
    {
        $this
            ->getJson(
                '/api/users/not-found'
            )
            ->assertNotFound();
    }

    public function test_inactive_profile_is_not_publicly_visible(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $user->status = 'suspended';
        $user->save();

        $this
            ->getJson('/api/users/alice')
            ->assertNotFound();
    }
}
