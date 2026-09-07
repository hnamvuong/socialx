<?php

namespace Tests\Feature;

use App\Models\Mention;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MentionDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_can_mention_user(): void
    {
        $author =
            User::factory()
                ->create();

        $mentionedUser =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $this->assertDatabaseHas(
            'mentions',
            [
                'post_id' => $post->id,

                'mentioned_user_id' => $mentionedUser->id,
            ]
        );
    }

    public function test_post_can_mention_multiple_users(): void
    {
        $author =
            User::factory()
                ->create();

        $alice =
            User::factory()
                ->create();

        $bob =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->mentionedUsers()
            ->attach([
                $alice->id,
                $bob->id,
            ]);

        $post->load(
            'mentionedUsers'
        );

        $this->assertCount(
            2,
            $post->mentionedUsers
        );
    }

    public function test_user_can_be_mentioned_in_multiple_posts(): void
    {
        $author =
            User::factory()
                ->create();

        $mentionedUser =
            User::factory()
                ->create();

        $firstPost =
            Post::factory()
                ->for($author)
                ->create();

        $secondPost =
            Post::factory()
                ->for($author)
                ->create();

        $firstPost
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $secondPost
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $mentionedUser->load(
            'mentionedInPosts'
        );

        $this->assertCount(
            2,
            $mentionedUser
                ->mentionedInPosts
        );
    }

    public function test_same_user_cannot_be_mentioned_twice_in_same_post(): void
    {
        $author =
            User::factory()
                ->create();

        $mentionedUser =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $this->expectException(
            QueryException::class
        );

        $post
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );
    }

    public function test_deleting_post_removes_mentions(): void
    {
        $author =
            User::factory()
                ->create();

        $mentionedUser =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $postId =
            $post->id;

        $post->delete();

        $this->assertDatabaseMissing(
            'mentions',
            [
                'post_id' => $postId,
            ]
        );
    }

    public function test_deleting_mentioned_user_removes_mentions(): void
    {
        $author =
            User::factory()
                ->create();

        $mentionedUser =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $mentionedUserId =
            $mentionedUser->id;

        $mentionedUser->delete();

        $this->assertDatabaseMissing(
            'mentions',
            [
                'mentioned_user_id' => $mentionedUserId,
            ]
        );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $post->id,
            ]
        );
    }

    public function test_mention_model_relations_work(): void
    {
        $author =
            User::factory()
                ->create();

        $mentionedUser =
            User::factory()
                ->create();

        $post =
            Post::factory()
                ->for($author)
                ->create();

        $post
            ->mentionedUsers()
            ->attach(
                $mentionedUser->id
            );

        $mention =
            Mention::query()
                ->firstOrFail();

        $this->assertTrue(
            $mention
                ->post
                ->is(
                    $post
                )
        );

        $this->assertTrue(
            $mention
                ->mentionedUser
                ->is(
                    $mentionedUser
                )
        );
    }

    private function actingAsUser(): User
    {
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'user')
            ->firstOrFail();

        $user->roles()->attach($role);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_duplicate_mentions_are_only_persisted_once(): void
    {
        $this->actingAsUser();

        User::factory()
            ->create([
                'username' => 'alice',
                'status' => 'active',
            ]);

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => '@alice @Alice @ALICE',
                ]
            )
            ->assertCreated();

        $post =
            Post::query()
                ->latest('id')
                ->firstOrFail();

        $this->assertSame(
            1,
            $post
                ->mentions()
                ->count()
        );
    }
}
