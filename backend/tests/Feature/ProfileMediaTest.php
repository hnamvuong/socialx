<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $file =
            UploadedFile::fake()
                ->image(
                    'avatar.jpg',
                    600,
                    600
                );

        $response =
            $this->postJson(
                '/api/profile/avatar',
                [
                    'avatar' => $file,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.user.username',
                'alice'
            );

        $user->refresh();

        $this->assertNotNull(
            $user->avatar_path
        );

        Storage::disk('public')
            ->assertExists(
                $user->avatar_path
            );
    }

    public function test_authenticated_user_can_upload_cover(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $file =
            UploadedFile::fake()
                ->image(
                    'cover.jpg',
                    1500,
                    500
                );

        $this->postJson(
            '/api/profile/cover',
            [
                'cover' => $file,
            ]
        )->assertOk();

        $user->refresh();

        Storage::disk('public')
            ->assertExists(
                $user->cover_path
            );
    }

    public function test_guest_cannot_upload_avatar(): void
    {
        Storage::fake('public');

        $file =
            UploadedFile::fake()
                ->image('avatar.jpg');

        $this
            ->postJson(
                '/api/profile/avatar',
                [
                    'avatar' => $file,
                ]
            )
            ->assertUnauthorized();
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('public');

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        Sanctum::actingAs($user);

        $file =
            UploadedFile::fake()
                ->create(
                    'document.pdf',
                    100,
                    'application/pdf'
                );

        $this
            ->postJson(
                '/api/profile/avatar',
                [
                    'avatar' => $file,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'avatar',
            ]);
    }

    public function test_old_avatar_is_deleted_after_replacement(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put(
            'avatars/old.jpg',
            'old-avatar'
        );

        $user = User::create([
            'name' => 'Alice',
            'username' => 'alice',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ]);

        $user->avatar_path =
            'avatars/old.jpg';

        $user->save();

        Sanctum::actingAs($user);

        $file =
            UploadedFile::fake()
                ->image('new.jpg');

        $this
            ->postJson(
                '/api/profile/avatar',
                [
                    'avatar' => $file,
                ]
            )
            ->assertOk();

        Storage::disk('public')
            ->assertMissing(
                'avatars/old.jpg'
            );
    }
}
