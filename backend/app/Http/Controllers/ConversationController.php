<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateDirectConversationRequest;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function storeDirect(
        CreateDirectConversationRequest $request
    ): JsonResponse {
        $viewer =
            $request->user();

        $validated =
            $request->validated();

        $target =
            User::query()
                ->where(
                    'id',
                    $validated['user_id']
                )
                ->where(
                    'status',
                    'active'
                )
                ->firstOrFail();

        abort_if(
            $viewer->id ===
                $target->id,
            422,
            'Bạn không thể tạo cuộc trò chuyện với chính mình.'
        );

        $directKey =
            $this->directKey(
                $viewer->id,
                $target->id
            );

        $conversation =
            Conversation::query()
                ->where(
                    'type',
                    Conversation::TYPE_DIRECT
                )
                ->where(
                    'direct_key',
                    $directKey
                )
                ->first();

        if ($conversation) {
            return response()->json([
                'data' => [
                    'conversation' => $this->conversationData(
                        $conversation,
                        $viewer
                    ),
                ],
            ]);
        }

        try {
            $conversation =
                DB::transaction(
                    function () use (
                        $viewer,
                        $target,
                        $directKey
                    ): Conversation {
                        $conversation =
                            Conversation::query()
                                ->create([
                                    'type' => Conversation::TYPE_DIRECT,

                                    'direct_key' => $directKey,

                                    'created_by' => $viewer->id,
                                ]);

                        $conversation
                            ->memberships()
                            ->createMany([
                                [
                                    'user_id' => $viewer->id,

                                    'role' => ConversationMember::ROLE_MEMBER,

                                    'joined_at' => now(),
                                ],

                                [
                                    'user_id' => $target->id,

                                    'role' => ConversationMember::ROLE_MEMBER,

                                    'joined_at' => now(),
                                ],
                            ]);

                        return $conversation;
                    }
                );
        } catch (QueryException $exception) {
            /*
             * Hai request có thể cùng vượt qua
             * bước kiểm tra phía trên.
             *
             * UNIQUE direct_key là lớp bảo vệ
             * cuối cùng ở database.
             */
            $conversation =
                Conversation::query()
                    ->where(
                        'type',
                        Conversation::TYPE_DIRECT
                    )
                    ->where(
                        'direct_key',
                        $directKey
                    )
                    ->first();

            if (! $conversation) {
                throw $exception;
            }
        }

        return response()->json(
            [
                'data' => [
                    'conversation' => $this->conversationData(
                        $conversation,
                        $viewer
                    ),
                ],
            ],
            201
        );
    }

    public function index(
        Request $request
    ): JsonResponse {
        $viewer =
            $request->user();

        $conversations =
            Conversation::query()
                ->whereHas(
                    'memberships',
                    function (
                        $query
                    ) use (
                        $viewer
                    ): void {
                        $query->where(
                            'user_id',
                            $viewer->id
                        );
                    }
                )
                ->with([
                    'members',
                ])
                ->orderByDesc(
                    'updated_at'
                )
                ->orderByDesc(
                    'id'
                )
                ->get();

        return response()->json([
            'data' => [
                'conversations' => $conversations
                    ->map(
                        fn (
                            Conversation $conversation
                        ): array => $this->conversationData(
                            $conversation,
                            $viewer
                        )
                    )
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function show(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $viewer =
            $request->user();

        $isMember =
            $conversation
                ->memberships()
                ->where(
                    'user_id',
                    $viewer->id
                )
                ->exists();

        abort_unless(
            $isMember,
            404
        );

        return response()->json([
            'data' => [
                'conversation' => $this->conversationData(
                    $conversation,
                    $viewer
                ),
            ],
        ]);
    }

    private function conversationData(
        Conversation $conversation,
        User $viewer
    ): array {
        $conversation->loadMissing([
            'members',
        ]);

        $members =
            $conversation
                ->members
                ->map(
                    fn (
                        User $member
                    ): array => [
                        'id' => $member->id,

                        'username' => $member->username,

                        'display_name' => $member->display_name,

                        'avatar_url' => $this->storage
                            ->publicUrl(
                                $member->avatar_path
                            ),
                    ]
                )
                ->values()
                ->all();

        $otherMember =
            null;

        if (
            $conversation->type ===
            Conversation::TYPE_DIRECT
        ) {
            $other =
                $conversation
                    ->members
                    ->first(
                        fn (
                            User $member
                        ): bool => $member->id !==
                            $viewer->id
                    );

            if ($other) {
                $otherMember = [
                    'id' => $other->id,

                    'username' => $other->username,

                    'display_name' => $other->display_name,

                    'avatar_url' => $this->storage
                        ->publicUrl(
                            $other->avatar_path
                        ),
                ];
            }
        }

        return [
            'id' => $conversation->id,

            'type' => $conversation->type,

            'title' => $conversation->title,

            'created_by' => $conversation->created_by,

            'members' => $members,

            'other_member' => $otherMember,

            'created_at' => $conversation->created_at,

            'updated_at' => $conversation->updated_at,
        ];
    }

    private function directKey(
        int $firstUserId,
        int $secondUserId
    ): string {
        $lower =
            min(
                $firstUserId,
                $secondUserId
            );

        $higher =
            max(
                $firstUserId,
                $secondUserId
            );

        return $lower
            .':'
            .$higher;
    }
}
