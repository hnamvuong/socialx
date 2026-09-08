<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReplyNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function authenticateUser(
        User $user
    ): void {
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
                $role
            );

        Sanctum::actingAs(
            $user
        );
    }

    private function replyUrl(
        Post $post
    ): string {
        return "/api/posts/{$post->id}/replies";
    }

    public function test_replying_to_another_users_post_creates_notification(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $parentPost =
            Post::factory()
                ->for($owner)
                ->create();

        $this->authenticateUser($actor);

        $response =
            $this->postJson(
                $this->replyUrl(
                    $parentPost
                ),
                [
                    'content' => 'Đây là một reply.',
                ]
            );

        $response
            ->assertCreated();

        $replyId =
            $response->json(
                'data.post.id'
            );

        $this->assertNotNull(
            $replyId
        );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $replyId,

                'user_id' => $actor->id,

                'parent_post_id' => $parentPost->id,

                'root_post_id' => $parentPost->id,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $owner->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_REPLY,

                'post_id' => $replyId,

                'read_at' => null,
            ]
        );
    }

    public function test_replying_to_own_post_does_not_create_notification(): void
    {
        $user =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $parentPost =
            Post::factory()
                ->for($user)
                ->create();

        $this->authenticateUser($user);

        $response =
            $this->postJson(
                $this->replyUrl(
                    $parentPost
                ),
                [
                    'content' => 'Tự reply bài của mình.',
                ]
            );

        $response
            ->assertCreated();

        $replyId =
            $response->json(
                'data.post.id'
            );

        $this->assertNotNull(
            $replyId
        );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $replyId,

                'user_id' => $user->id,

                'parent_post_id' => $parentPost->id,
            ]
        );

        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $user->id,

                'actor_id' => $user->id,

                'type' => Notification::TYPE_REPLY,

                'post_id' => $replyId,
            ]
        );
    }

    public function test_nested_reply_notifies_direct_parent_author(): void
    {
        $rootOwner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $firstReplier =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $secondReplier =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $rootPost =
            Post::factory()
                ->for($rootOwner)
                ->create();

        /*
         * Alice reply Bob.
         */
        $this->authenticateUser($firstReplier);

        $firstResponse =
            $this->postJson(
                $this->replyUrl(
                    $rootPost
                ),
                [
                    'content' => 'Reply level 1',
                ]
            );

        $firstResponse
            ->assertCreated();

        $firstReplyId =
            $firstResponse->json(
                'data.post.id'
            );

        $firstReply =
            Post::query()
                ->findOrFail(
                    $firstReplyId
                );

        /*
         * Charlie reply Alice.
         */
        $this->authenticateUser($secondReplier);

        $secondResponse =
            $this->postJson(
                $this->replyUrl(
                    $firstReply
                ),
                [
                    'content' => 'Reply level 2',
                ]
            );

        $secondResponse
            ->assertCreated();

        $secondReplyId =
            $secondResponse->json(
                'data.post.id'
            );

        $this->assertDatabaseHas(
            'posts',
            [
                'id' => $secondReplyId,

                'parent_post_id' => $firstReply->id,

                'root_post_id' => $rootPost->id,
            ]
        );

        /*
         * Notification của reply level 2
         * phải gửi tới owner của parent trực tiếp.
         */
        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $firstReplier->id,

                'actor_id' => $secondReplier->id,

                'type' => Notification::TYPE_REPLY,

                'post_id' => $secondReplyId,
            ]
        );

        /*
         * Bob không nhận Reply Notification
         * cho reply level 2.
         */
        $this->assertDatabaseMissing(
            'notifications',
            [
                'user_id' => $rootOwner->id,

                'actor_id' => $secondReplier->id,

                'type' => Notification::TYPE_REPLY,

                'post_id' => $secondReplyId,
            ]
        );
    }

    public function test_multiple_replies_create_multiple_notifications(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $parentPost =
            Post::factory()
                ->for($owner)
                ->create();

        $this->authenticateUser($actor);

        $firstResponse =
            $this->postJson(
                $this->replyUrl(
                    $parentPost
                ),
                [
                    'content' => 'Reply thứ nhất',
                ]
            );

        $firstResponse
            ->assertCreated();

        $secondResponse =
            $this->postJson(
                $this->replyUrl(
                    $parentPost
                ),
                [
                    'content' => 'Reply thứ hai',
                ]
            );

        $secondResponse
            ->assertCreated();

        $notificationCount =
            Notification::query()
                ->where(
                    'user_id',
                    $owner->id
                )
                ->where(
                    'actor_id',
                    $actor->id
                )
                ->where(
                    'type',
                    Notification::TYPE_REPLY
                )
                ->count();

        $this->assertSame(
            2,
            $notificationCount
        );
    }

    public function test_deleting_reply_removes_its_notification(): void
    {
        $owner =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $actor =
            User::factory()
                ->create([
                    'status' => 'active',
                ]);

        $parentPost =
            Post::factory()
                ->for($owner)
                ->create();

        $this->authenticateUser($actor);

        $response =
            $this->postJson(
                $this->replyUrl(
                    $parentPost
                ),
                [
                    'content' => 'Reply sẽ bị xóa.',
                ]
            );

        $response
            ->assertCreated();

        $replyId =
            $response->json(
                'data.post.id'
            );

        $this->assertDatabaseHas(
            'notifications',
            [
                'user_id' => $owner->id,

                'actor_id' => $actor->id,

                'type' => Notification::TYPE_REPLY,

                'post_id' => $replyId,
            ]
        );

        $this->deleteJson(
            "/api/posts/{$replyId}"
        )
            ->assertNoContent();

        $this->assertDatabaseMissing(
            'notifications',
            [
                'post_id' => $replyId,

                'type' => Notification::TYPE_REPLY,
            ]
        );
    }
}
