<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users =
            User::query()
                ->where(
                    'status',
                    'active'
                )
                ->orderBy('id')
                ->take(3)
                ->get();

        if ($users->count() < 3) {
            $this->command?->warn(
                'Cần ít nhất 3 active users để seed chat demo.'
            );

            return;
        }

        $alice =
            $users[0];

        $bob =
            $users[1];

        $charlie =
            $users[2];

        $aliceBob =
            $this->createDirectConversation(
                $alice,
                $bob
            );

        $aliceCharlie =
            $this->createDirectConversation(
                $alice,
                $charlie
            );

        $this->createMessages(
            $aliceBob,
            [
                [
                    'sender_id' => $alice->id,

                    'body' => 'Chào Bob, bạn khỏe không?',
                ],
                [
                    'sender_id' => $bob->id,

                    'body' => 'Mình khỏe, còn bạn?',
                ],
                [
                    'sender_id' => $alice->id,

                    'body' => 'Mình cũng ổn. Hẹn gặp bạn ngày mai nhé.',
                ],
            ]
        );

        $this->createMessages(
            $aliceCharlie,
            [
                [
                    'sender_id' => $charlie->id,

                    'body' => 'Bạn đã xem phần chat mới chưa?',
                ],
                [
                    'sender_id' => $alice->id,

                    'body' => 'Mình đang test đây.',
                ],
            ]
        );

        $this->command?->info(
            'Chat demo data created.'
        );
    }

    private function createDirectConversation(
        User $first,
        User $second
    ): Conversation {
        $directKey =
            min(
                $first->id,
                $second->id
            )
            .':'
            .max(
                $first->id,
                $second->id
            );

        $conversation =
            Conversation::query()
                ->firstOrCreate(
                    [
                        'direct_key' => $directKey,
                    ],
                    [
                        'type' => Conversation::TYPE_DIRECT,

                        'created_by' => $first->id,
                    ]
                );

        $conversation
            ->memberships()
            ->firstOrCreate(
                [
                    'user_id' => $first->id,
                ],
                [
                    'role' => ConversationMember::ROLE_MEMBER,

                    'joined_at' => now(),
                ]
            );

        $conversation
            ->memberships()
            ->firstOrCreate(
                [
                    'user_id' => $second->id,
                ],
                [
                    'role' => ConversationMember::ROLE_MEMBER,

                    'joined_at' => now(),
                ]
            );

        return $conversation;
    }

    private function createMessages(
        Conversation $conversation,
        array $messages
    ): void {
        if (
            $conversation
                ->messages()
                ->exists()
        ) {
            return;
        }

        foreach (
            $messages as $message
        ) {
            $conversation
                ->messages()
                ->create(
                    $message
                );
        }

        $conversation->touch();
    }
}
