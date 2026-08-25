<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'user',
            'display_name' => 'User',
        ]);
    }

    /**
     * A basic feature test example.
     */
    public function test_user_can_register(): void
    {
        $payload = [
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->postJson('/api/auth/register', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.user.username',
                'alice'
            );

        $this->assertDatabaseHas('users', [
            'username' => 'alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => 1,
        ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => 1,
            'role_id' => 1,
        ]);
    }

    public function test_username_must_be_unique(): void
    {
        $payload = [
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson(
            '/api/auth/register',
            $payload
        )->assertCreated();

        $response = $this->postJson(
            '/api/auth/register',
            [
                ...$payload,
                'email' => 'alice2@example.com',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'username',
            ]);
    }

    public function test_email_must_be_unique(): void
    {
        $payload1 = [
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/auth/register', $payload1)->assertCreated();

        $payload2 = [
            'username' => 'alice2',
            'display_name' => 'Alice 2',
            'email' => 'alice@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $payload2);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }
}
