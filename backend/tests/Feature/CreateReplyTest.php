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

class CreateReplyTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $this->seed();

        $user = User::factory()->create();

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

    public function test_user_can_reply_to_root_post(): void
    {
        $user =
            $this->actingAsUser();

        $root =
            Post::factory()->create([
                'content' => 'Root post',
            ]);

        $response =
            $this->postJson(
                "/api/posts/{$root->id}/replies",
                [
                    'content' => 'Reply cấp 1',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.post.content',
                'Reply cấp 1'
            )
            ->assertJsonPath(
                'data.post.parent_post_id',
                $root->id
            )
            ->assertJsonPath(
                'data.post.root_post_id',
                $root->id
            )
            ->assertJsonPath(
                'data.post.user.id',
                $user->id
            );

        $replyId =
            $response->json(
                'data.post.id'
            );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $replyId,

                'user_id' => $user->id,

                'parent_post_id' => $root->id,

                'root_post_id' => $root->id,
            ]
        );
    }

    public function test_nested_reply_keeps_original_root(): void
    {
        $this->actingAsUser();

        $root =
            Post::factory()->create();

        $firstReply =
            Post::factory()->create();

        $firstReply->parent_post_id =
            $root->id;

        $firstReply->root_post_id =
            $root->id;

        $firstReply->save();

        $response =
            $this->postJson(
                "/api/posts/{$firstReply->id}/replies",
                [
                    'content' => 'Reply cấp 2',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.post.parent_post_id',
                $firstReply->id
            )
            ->assertJsonPath(
                'data.post.root_post_id',
                $root->id
            );
    }

    public function test_client_cannot_control_reply_thread_ids(): void
    {
        $this->actingAsUser();

        $root =
            Post::factory()->create();

        $fakePost =
            Post::factory()->create();

        $response =
            $this->postJson(
                "/api/posts/{$root->id}/replies",
                [
                    'content' => 'Reply',

                    'parent_post_id' => $fakePost->id,

                    'root_post_id' => $fakePost->id,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.post.parent_post_id',
                $root->id
            )
            ->assertJsonPath(
                'data.post.root_post_id',
                $root->id
            );
    }

    public function test_empty_reply_is_rejected(): void
    {
        $this->actingAsUser();

        $post =
            Post::factory()->create();

        $this
            ->postJson(
                "/api/posts/{$post->id}/replies",
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'content',
            ]);
    }

    public function test_reply_content_cannot_exceed_280_characters(): void
    {
        $this->actingAsUser();

        $post =
            Post::factory()->create();

        $this
            ->postJson(
                "/api/posts/{$post->id}/replies",
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

    public function test_reply_can_have_images(): void
    {
        Storage::fake('public');

        $this->actingAsUser();

        $parent =
            Post::factory()->create();

        $image =
            UploadedFile::fake()
                ->image(
                    'reply.jpg',
                    800,
                    600
                );

        $response =
            $this->post(
                "/api/posts/{$parent->id}/replies",
                [
                    'content' => 'Reply có hình',

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
            ->assertJsonCount(
                1,
                'data.post.media'
            );

        $reply =
            Post::findOrFail(
                $response->json(
                    'data.post.id'
                )
            );

        $media =
            $reply->media()->firstOrFail();

        Storage::disk('public')
            ->assertExists(
                $media->path
            );
    }

    public function test_media_only_reply_is_allowed(): void
    {
        Storage::fake('public');

        $this->actingAsUser();

        $parent =
            Post::factory()->create();

        $image =
            UploadedFile::fake()
                ->image('reply.jpg');

        $response =
            $this->post(
                "/api/posts/{$parent->id}/replies",
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
            );
    }

    public function test_reply_cannot_have_more_than_four_images(): void
    {
        Storage::fake('public');

        $this->actingAsUser();

        $parent =
            Post::factory()->create();

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
                "/api/posts/{$parent->id}/replies",
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

    public function test_guest_cannot_reply(): void
    {
        $parent =
            Post::factory()->create();

        $this
            ->postJson(
                "/api/posts/{$parent->id}/replies",
                [
                    'content' => 'Reply',
                ]
            )
            ->assertUnauthorized();
    }

    public function test_reply_to_unknown_post_returns_404(): void
    {
        $this->actingAsUser();

        $this
            ->postJson(
                '/api/posts/999999/replies',
                [
                    'content' => 'Reply',
                ]
            )
            ->assertNotFound();
    }

    public function test_cannot_reply_to_post_of_inactive_user(): void
    {
        $this->actingAsUser();

        $author =
            User::factory()->create();

        $author->status =
            'suspended';

        $author->save();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $this
            ->postJson(
                "/api/posts/{$post->id}/replies",
                [
                    'content' => 'Reply',
                ]
            )
            ->assertNotFound();
    }
}
