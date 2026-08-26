<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson(
            '/api/profile',
            [
                'display_name' => 'Alice Nguyễn',
                'bio' => 'Frontend developer',
                'location' => 'Tokyo',
                'website' => 'https://example.com',
            ],
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.user.display_name',
                'Alice Nguyễn',
            )
            ->assertJsonPath(
                'data.user.bio',
                'Frontend developer',
            );

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
                'display_name' => 'Alice Nguyễn',
                'location' => 'Tokyo',
            ],
        );
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this
            ->patchJson(
                '/api/profile',
                [
                    'display_name' => 'Alice',
                ],
            )
            ->assertUnauthorized();
    }

    public function test_display_name_is_required(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $this
            ->patchJson(
                '/api/profile',
                [
                    'display_name' => '',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'display_name',
            ]);
    }

    public function test_website_must_be_valid_url(): void
    {
        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $this
            ->patchJson(
                '/api/profile',
                [
                    'display_name' => 'Alice',
                    'website' => 'not-a-valid-url',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'website',
            ]);
    }
}
