<?php

namespace Tests\Feature;

use App\Events\UserMentioned;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MentionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            RolePermissionSeeder::class
        );
    }

    public function test_creating_post_dispatches_mention_event(): void
    {
        Event::fake([
            UserMentioned::class,
        ]);

        $author =
            User::factory()
                ->create();

        $role =
            Role::query()
                ->where(
                    'name',
                    'user'
                )
                ->firstOrFail();

        $author
            ->roles()
            ->attach(
                $role
            );

        $mentionedUser =
            User::factory()
                ->create([
                    'username' => 'alice',
                    'status' => 'active',
                ]);

        Sanctum::actingAs(
            $author
        );

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Hello @alice',
                ]
            )
            ->assertCreated();

        Event::assertDispatched(
            UserMentioned::class,
            function (
                UserMentioned $event
            ) use (
                $author,
                $mentionedUser
            ): bool {
                return
                    $event->actor->is(
                        $author
                    )
                    &&
                    $event
                        ->mentionedUser
                        ->is(
                            $mentionedUser
                        );
            }
        );
    }

    public function test_unknown_username_does_not_dispatch_mention_event(): void
    {
        Event::fake([
            UserMentioned::class,
        ]);

        $author =
            User::factory()
                ->create();

        $role =
            Role::query()
                ->where(
                    'name',
                    'user'
                )
                ->firstOrFail();

        $author
            ->roles()
            ->attach(
                $role
            );

        Sanctum::actingAs(
            $author
        );

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Hello @not_existing_user',
                ]
            )
            ->assertCreated();

        Event::assertNotDispatched(
            UserMentioned::class
        );
    }

    public function test_user_does_not_receive_mention_event_for_self_mention(): void
    {
        Event::fake([
            UserMentioned::class,
        ]);

        $author =
            User::factory()
                ->create([
                    'username' => 'alice',
                ]);

        $role =
            Role::query()
                ->where(
                    'name',
                    'user'
                )
                ->firstOrFail();

        $author
            ->roles()
            ->attach(
                $role
            );

        Sanctum::actingAs(
            $author
        );

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => 'Hello @alice',
                ]
            )
            ->assertCreated();

        Event::assertNotDispatched(
            UserMentioned::class
        );

        $post =
            Post::query()
                ->latest('id')
                ->firstOrFail();

        /*
         * Relation vẫn được lưu,
         * chỉ notification bị bỏ qua.
         */
        $this->assertDatabaseHas(
            'mentions',
            [
                'post_id' => $post->id,

                'mentioned_user_id' => $author->id,
            ]
        );
    }

    public function test_duplicate_mentions_dispatch_only_one_event(): void
    {
        Event::fake([
            UserMentioned::class,
        ]);

        $author =
            User::factory()
                ->create();

        $role =
            Role::query()
                ->where(
                    'name',
                    'user'
                )
                ->firstOrFail();

        $author
            ->roles()
            ->attach(
                $role
            );

        $alice =
            User::factory()
                ->create([
                    'username' => 'alice',
                    'status' => 'active',
                ]);

        Sanctum::actingAs(
            $author
        );

        $this
            ->postJson(
                '/api/posts',
                [
                    'content' => '@alice @Alice @ALICE',
                ]
            )
            ->assertCreated();

        Event::assertDispatchedTimes(
            UserMentioned::class,
            1
        );
    }

    public function test_updating_post_does_not_notify_existing_mention_again(): void
    {
        Event::fake([
            UserMentioned::class,
        ]);

        $author =
            User::factory()
                ->create();

        $alice =
            User::factory()
                ->create([
                    'username' => 'alice',
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($author)
                ->create([
                    'content' => '@alice',
                ]);

        $post
            ->mentionedUsers()
            ->attach(
                $alice->id
            );

        /*
         * Setup authentication/permission cho
         * route update theo đúng test update
         * hiện tại của project.
         */
        Sanctum::actingAs(
            $author
        );

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => 'Hello @alice',
                ]
            )
            ->assertOk();

        Event::assertNotDispatched(
            UserMentioned::class
        );
    }

    public function test_updating_post_notifies_only_newly_mentioned_user(): void
    {
        Event::fake([
            UserMentioned::class,
        ]);

        $author =
            User::factory()
                ->create();

        $alice =
            User::factory()
                ->create([
                    'username' => 'alice',
                    'status' => 'active',
                ]);

        $bob =
            User::factory()
                ->create([
                    'username' => 'bob',
                    'status' => 'active',
                ]);

        $post =
            Post::factory()
                ->for($author)
                ->create([
                    'content' => '@alice',
                ]);

        $post
            ->mentionedUsers()
            ->attach(
                $alice->id
            );

        Sanctum::actingAs(
            $author
        );

        $this
            ->patchJson(
                "/api/posts/{$post->id}",
                [
                    'content' => '@alice @bob',
                ]
            )
            ->assertOk();

        Event::assertDispatchedTimes(
            UserMentioned::class,
            1
        );

        Event::assertDispatched(
            UserMentioned::class,
            fn (
                UserMentioned $event
            ): bool => $event
                ->mentionedUser
                ->is(
                    $bob
                )
        );
    }
}
