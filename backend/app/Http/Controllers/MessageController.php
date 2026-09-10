<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MessageController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function store(
        SendMessageRequest $request,
        Conversation $conversation
    ): JsonResponse {
        $user =
            $request->user();

        $isMember =
            $conversation
                ->memberships()
                ->where(
                    'user_id',
                    $user->id
                )
                ->exists();

        abort_unless(
            $isMember,
            404
        );

        $validated =
            $request->validated();

        $storedPaths = [];

        try {
            $message =
                DB::transaction(
                    function () use (
                        $request,
                        $conversation,
                        $user,
                        $validated,
                        &$storedPaths
                    ): Message {
                        $message =
                            $conversation
                                ->messages()
                                ->create([
                                    'sender_id' => $user->id,

                                    'body' => $validated['body']
                                        ?? null,
                                ]);

                        $files =
                            $request->file(
                                'attachments',
                                []
                            );

                        foreach (
                            $files as $index => $file
                        ) {
                            $path =
                                $this->storage
                                    ->storePublicImage(
                                        $file,
                                        "messages/{$message->id}"
                                    );

                            $storedPaths[] =
                                $path;

                            $dimensions =
                                getimagesize(
                                    $file->getRealPath()
                                );

                            $width =
                                is_array(
                                    $dimensions
                                )
                                    ? $dimensions[0]
                                    : null;

                            $height =
                                is_array(
                                    $dimensions
                                )
                                    ? $dimensions[1]
                                    : null;

                            $message
                                ->attachments()
                                ->create([
                                    'type' => MessageAttachment::TYPE_IMAGE,

                                    'path' => $path,

                                    'mime_type' => $file->getMimeType(),

                                    'size' => $file->getSize(),

                                    'width' => $width,

                                    'height' => $height,

                                    'sort_order' => $index,
                                ]);
                        }

                        /*
                         * updated_at của conversation
                         * đại diện activity mới nhất.
                         */
                        $conversation->touch();

                        return $message;
                    }
                );
        } catch (Throwable $exception) {
            foreach (
                $storedPaths as $path
            ) {
                $this->storage
                    ->deletePublic(
                        $path
                    );
            }

            throw $exception;
        }

        $recipientIds =
            $conversation
                ->memberships()
                ->where(
                    'user_id',
                    '!=',
                    $user->id
                )
                ->pluck(
                    'user_id'
                )
                ->map(
                    fn ($id): int => (int) $id
                )
                ->values()
                ->all();

        MessageSent::dispatch(
            $message,
            $recipientIds
        );

        return response()->json(
            [
                'data' => [
                    'message' => $this->messageData(
                        $message
                    ),
                ],
            ],
            201
        );
    }

    private function messageData(
        Message $message
    ): array {
        $message->loadMissing([
            'sender',
            'attachments',
        ]);

        return [
            'id' => $message->id,

            'conversation_id' => $message->conversation_id,

            'body' => $message->body,

            'sender' => $this->senderData(
                $message->sender
            ),

            'attachments' => $message
                ->attachments
                ->map(
                    fn (
                        MessageAttachment $attachment
                    ): array => [
                        'id' => $attachment->id,

                        'type' => $attachment->type,

                        'url' => $this->storage
                            ->publicUrl(
                                $attachment->path
                            ),

                        'mime_type' => $attachment->mime_type,

                        'size' => $attachment->size,

                        'width' => $attachment->width,

                        'height' => $attachment->height,

                        'sort_order' => $attachment->sort_order,
                    ]
                )
                ->values()
                ->all(),

            'created_at' => $message->created_at,

            'updated_at' => $message->updated_at,
        ];
    }

    private function senderData(
        User $user
    ): array {
        return [
            'id' => $user->id,

            'username' => $user->username,

            'display_name' => $user->display_name,

            'avatar_url' => $this->storage
                ->publicUrl(
                    $user->avatar_path
                ),
        ];
    }

    public function index(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $user =
            $request->user();

        $isMember =
            $conversation
                ->memberships()
                ->where(
                    'user_id',
                    $user->id
                )
                ->exists();

        abort_unless(
            $isMember,
            404
        );

        $paginator =
            Message::query()
                ->where(
                    'conversation_id',
                    $conversation->id
                )
                ->with([
                    'sender',
                    'attachments',
                ])
                ->orderByDesc(
                    'id'
                )
                ->cursorPaginate(
                    perPage: 30
                );

        return response()->json([
            'data' => [
                'messages' => collect(
                    $paginator->items()
                )
                    ->map(
                        fn (
                            Message $message
                        ): array => $this->messageData(
                            $message
                        )
                    )
                    ->values()
                    ->all(),

                'pagination' => [
                    'per_page' => $paginator->perPage(),

                    'next_cursor' => $paginator
                        ->nextCursor()
                        ?->encode(),

                    'has_more' => $paginator
                        ->hasMorePages(),
                ],
            ],
        ]);
    }
}
