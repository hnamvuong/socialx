<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreatePostTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $this->seed();

        $user =
            User::factory()->create();

        $role =
            Role::query()
                ->where(
                    'name',
                    'user'
                )
                ->firstOrFail();

        $user
            ->roles()
            ->attach(
                $role->id
            );

        Sanctum::actingAs(
            $user
        );

        return $user;
    }

    public function test_authenticated_user_can_create_text_post(): void
    {
        $user =
            $this->actingAsUser();

        $response =
            $this->postJson(
                '/api/posts',
                [
                    'content' => 'Hello SocialX',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.post.content',
                'Hello SocialX'
            )
            ->assertJsonPath(
                'data.post.user.id',
                $user->id
            );

        $this->assertDatabaseHas(
            'posts',
            [
                'user_id' => $user->id,

                'content' => 'Hello SocialX',
            ]
        );
    }

    public function test_post_is_always_owned_by_authenticated_user(): void
    {
        $user =
            $this->actingAsUser();

        $otherUser =
            User::factory()->create();

        $response =
            $this->postJson(
                '/api/posts',
                [
                    'content' => 'My post',

                    'user_id' => $otherUser->id,
                ]
            );

        $response->assertCreated();

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $response->json(
                    'data.post.id'
                ),

                'user_id' => $user->id,
            ]
        );

        $this->assertDatabaseMissing(
            'posts',
            [
                'id' => $response->json(
                    'data.post.id'
                ),

                'user_id' => $otherUser->id,
            ]
        );
    }

    public function test_empty_post_is_rejected(): void
    {
        $this->actingAsUser();

        $this
            ->postJson(
                '/api/posts',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content',
            ]);
    }

    public function test_whitespace_only_post_is_rejected(): void
    {
        $this->actingAsUser();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => '       ',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content',
            ]);
    }

    public function test_post_content_cannot_exceed_280_characters(): void
    {
        $this->actingAsUser();

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => str_repeat(
                        'a',
                        281
                    ),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content',
            ]);
    }

    public function test_user_can_create_post_with_images(): void
    {
        Storage::fake('public');

        $user =
            $this->actingAsUser();

        $first =
            UploadedFile::fake()
                ->image(
                    'first.jpg',
                    1200,
                    800
                );

        $second =
            UploadedFile::fake()
                ->image(
                    'second.jpg',
                    800,
                    800
                );

        $response =
            $this->post(
                '/api/posts',
                [
                    'content' => 'Post có ảnh',

                    'media' => [
                        $first,
                        $second,
                    ],
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonCount(
                2,
                'data.post.media'
            )
            ->assertJsonPath(
                'data.post.user.id',
                $user->id
            );

        $postId =
            $response->json(
                'data.post.id'
            );

        $this->assertDatabaseCount(
            'post_media',
            2
        );

        $media =
            Post::findOrFail(
                $postId
            )->media;

        Storage::disk('public')
            ->assertExists(
                $media[0]->path
            );

        Storage::disk('public')
            ->assertExists(
                $media[1]->path
            );
    }

    public function test_user_can_create_media_only_post(): void
    {
        Storage::fake('public');

        $this->actingAsUser();

        $image =
            UploadedFile::fake()
                ->image(
                    'photo.jpg'
                );

        $response =
            $this->post(
                '/api/posts',
                [
                    'media' => [
                        $image,
                    ],
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.post.content',
                null
            )
            ->assertJsonCount(
                1,
                'data.post.media'
            );
    }

    public function test_post_cannot_have_more_than_four_images(): void
    {
        Storage::fake('public');

        $this->actingAsUser();

        $files = [];

        for ($i = 0; $i < 5; $i++) {
            $files[] =
                UploadedFile::fake()
                    ->image(
                        "image-{$i}.jpg"
                    );
        }

        $this
            ->post(
                '/api/posts',
                [
                    'media' => $files,
                ],
                [
                    'Accept' => 'application/json',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'media',
            ]);
    }

    public function test_guest_cannot_create_post(): void
    {
        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Hello',
                ]
            )
            ->assertUnauthorized();
    }
}
