<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_direct_conversation(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create();

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $target->id,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.conversation.type',
                Conversation::TYPE_DIRECT
            )
            ->assertJsonPath(
                'data.conversation.other_member.id',
                $target->id
            );

        $this->assertDatabaseHas(
            'conversations',
            [
                'type' => Conversation::TYPE_DIRECT,

                'direct_key' => min(
                    $viewer->id,
                    $target->id
                )
                    .':'
                    .max(
                        $viewer->id,
                        $target->id
                    ),
            ]
        );

        $this->assertDatabaseHas(
            'conversation_members',
            [
                'user_id' => $viewer->id,
            ]
        );

        $this->assertDatabaseHas(
            'conversation_members',
            [
                'user_id' => $target->id,
            ]
        );
    }

    public function test_direct_conversation_is_idempotent(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        Sanctum::actingAs(
            $alice
        );

        $first =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $bob->id,
                ]
            );

        $first->assertCreated();

        $conversationId =
            $first->json(
                'data.conversation.id'
            );

        $second =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $bob->id,
                ]
            );

        $second
            ->assertOk()
            ->assertJsonPath(
                'data.conversation.id',
                $conversationId
            );

        $this->assertDatabaseCount(
            'conversations',
            1
        );
    }

    public function test_reverse_direction_reuses_same_direct_conversation(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        Sanctum::actingAs(
            $alice
        );

        $first =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $bob->id,
                ]
            );

        $conversationId =
            $first->json(
                'data.conversation.id'
            );

        Sanctum::actingAs(
            $bob
        );

        $second =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $alice->id,
                ]
            );

        $second
            ->assertOk()
            ->assertJsonPath(
                'data.conversation.id',
                $conversationId
            );

        $this->assertDatabaseCount(
            'conversations',
            1
        );
    }

    public function test_user_cannot_create_direct_conversation_with_self(): void
    {
        $viewer =
            User::factory()->create();

        Sanctum::actingAs(
            $viewer
        );

        $this->postJson(
            '/api/conversations/direct',
            [
                'user_id' => $viewer->id,
            ]
        )
            ->assertStatus(422);
    }

    public function test_user_can_list_only_own_conversations(): void
    {
        $viewer =
            User::factory()->create();

        $target =
            User::factory()->create();

        $otherUser =
            User::factory()->create();

        Sanctum::actingAs(
            $viewer
        );

        $own =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $target->id,
                ]
            );

        $ownId =
            $own->json(
                'data.conversation.id'
            );

        Sanctum::actingAs(
            $target
        );

        $other =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $otherUser->id,
                ]
            );

        $otherId =
            $other->json(
                'data.conversation.id'
            );

        Sanctum::actingAs(
            $viewer
        );

        $response =
            $this->getJson(
                '/api/conversations'
            );

        $response->assertOk();

        $conversationIds =
            collect(
                $response->json(
                    'data.conversations'
                )
            )
                ->pluck('id');

        $this->assertTrue(
            $conversationIds
                ->contains(
                    $ownId
                )
        );

        $this->assertFalse(
            $conversationIds
                ->contains(
                    $otherId
                )
        );
    }

    public function test_non_member_cannot_view_conversation(): void
    {
        $alice =
            User::factory()->create();

        $bob =
            User::factory()->create();

        $intruder =
            User::factory()->create();

        Sanctum::actingAs(
            $alice
        );

        $response =
            $this->postJson(
                '/api/conversations/direct',
                [
                    'user_id' => $bob->id,
                ]
            );

        $conversationId =
            $response->json(
                'data.conversation.id'
            );

        Sanctum::actingAs(
            $intruder
        );

        $this->getJson(
            "/api/conversations/{$conversationId}"
        )
            ->assertNotFound();
    }
}
