<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SendMessageApiTest extends TestCase
{
    use RefreshDatabase;

    private function createDirectConversation(
        User $first,
        User $second
    ): Conversation {
        $conversation =
            Conversation::query()
                ->create([
                    'type' => Conversation::TYPE_DIRECT,

                    'direct_key' => min(
                        $first->id,
                        $second->id
                    )
                        .':'
                        .max(
                            $first->id,
                            $second->id
                        ),

                    'created_by' => $first->id,
                ]);

        $conversation
            ->memberships()
            ->createMany([
                [
                    'user_id' => $first->id,

                    'role' => ConversationMember::ROLE_MEMBER,

                    'joined_at' => now(),
                ],

                [
                    'user_id' => $second->id,

                    'role' => ConversationMember::ROLE_MEMBER,

                    'joined_at' => now(),
                ],
            ]);

        return $conversation;
    }

    public function test_member_can_send_text_message(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $conversation =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        Sanctum::actingAs(
            $alice
        );

        $response =
            $this->postJson(
                "/api/conversations/{$conversation->id}/messages",
                [
                    'body' => 'Xin chào Bob',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.message.body',
                'Xin chào Bob'
            )
            ->assertJsonPath(
                'data.message.sender.id',
                $alice->id
            )
            ->assertJsonPath(
                'data.message.conversation_id',
                $conversation->id
            );

        $this->assertDatabaseHas(
            'messages',
            [
                'conversation_id' => $conversation->id,

                'sender_id' => $alice->id,

                'body' => 'Xin chào Bob',
            ]
        );
    }

    public function test_non_member_cannot_send_message(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $intruder =
            User::factory()->create();

        $conversation =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        Sanctum::actingAs(
            $intruder
        );

        $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            [
                'body' => 'Unauthorized message',
            ]
        )
            ->assertNotFound();

        $this->assertDatabaseCount(
            'messages',
            0
        );
    }

    public function test_message_requires_body_or_attachment(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $conversation =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        Sanctum::actingAs(
            $alice
        );

        $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            []
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'body',
                'attachments',
            ]);

        $this->assertDatabaseCount(
            'messages',
            0
        );
    }

    public function test_member_can_send_message_with_image(): void
    {
        Storage::fake(
            'public'
        );

        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $conversation =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        Sanctum::actingAs(
            $alice
        );

        $image =
            UploadedFile::fake()
                ->image(
                    'chat.jpg',
                    800,
                    600
                );

        $response =
            $this->post(
                "/api/conversations/{$conversation->id}/messages",
                [
                    'attachments' => [
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
                'data.message.sender.id',
                $alice->id
            )
            ->assertJsonCount(
                1,
                'data.message.attachments'
            )
            ->assertJsonPath(
                'data.message.attachments.0.type',
                'image'
            );

        $this->assertDatabaseHas(
            'messages',
            [
                'conversation_id' => $conversation->id,

                'sender_id' => $alice->id,

                'body' => null,
            ]
        );

        $this->assertDatabaseCount(
            'message_attachments',
            1
        );
    }

    public function test_sending_message_touches_conversation(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $conversation =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        $conversation->forceFill([
            'updated_at' => now()->subDay(),
        ])->save();

        $oldUpdatedAt =
            $conversation
                ->updated_at;

        Sanctum::actingAs(
            $alice
        );

        $this->postJson(
            "/api/conversations/{$conversation->id}/messages",
            [
                'body' => 'Tin nhắn mới',
            ]
        )
            ->assertCreated();

        $conversation->refresh();

        $this->assertTrue(
            $conversation
                ->updated_at
                ->greaterThan(
                    $oldUpdatedAt
                )
        );
    }

    public function test_sender_is_always_authenticated_user(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $conversation =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        Sanctum::actingAs(
            $alice
        );

        $response =
            $this->postJson(
                "/api/conversations/{$conversation->id}/messages",
                [
                    'body' => 'Hello',

                    'sender_id' => $bob->id,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.message.sender.id',
                $alice->id
            );

        $this->assertDatabaseHas(
            'messages',
            [
                'conversation_id' => $conversation->id,

                'sender_id' => $alice->id,
            ]
        );
    }
}
