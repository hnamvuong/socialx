<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly StorageService $storage
    ) {}

    public function index(
        Request $request
    ): JsonResponse {
        $user =
            $request->user();

        $paginator =
            Notification::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->with([
                    'actor',
                    'post',
                ])
                ->orderByDesc(
                    'created_at'
                )
                ->orderByDesc(
                    'id'
                )
                ->cursorPaginate(
                    perPage: 20
                );

        $notifications =
                collect(
                    $paginator->items()
                )
                    ->map(
                        function (
                            Notification $notification
                        ): array {
                            return [
                                'id' => $notification->id,

                                'type' => $notification->type,

                                'read_at' => $notification->read_at,

                                'created_at' => $notification->created_at,

                                'actor' => [
                                    'id' => $notification
                                        ->actor
                                        ->id,

                                    'username' => $notification
                                        ->actor
                                        ->username,

                                    'display_name' => $notification
                                        ->actor
                                        ->display_name,

                                    'avatar_url' => $this
                                        ->storage
                                        ->publicUrl(
                                            $notification
                                                ->actor
                                                ->avatar_path
                                        ),
                                ],

                                'post' => $notification->post
                                        ? [
                                            'id' => $notification
                                                ->post
                                                ->id,

                                            'content' => $notification
                                                ->post
                                                ->content,
                                        ]
                                        : null,
                            ];
                        }
                    )
                    ->values()
                    ->all();

        return response()->json([
            'data' => [
                'notifications' => $notifications,

                'pagination' => [
                    'per_page' => $paginator
                        ->perPage(),

                    'next_cursor' => $paginator
                        ->nextCursor()
                        ?->encode(),

                    'has_more' => $paginator
                        ->hasMorePages(),
                ],
            ],
        ]);
    }

    public function unreadCount(
        Request $request
    ): JsonResponse {
        $user =
            $request->user();

        $count =
            Notification::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->whereNull(
                    'read_at'
                )
                ->count();

        return response()->json([
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function markAsRead(
        Request $request,
        Notification $notification
    ): JsonResponse {
        $user =
            $request->user();

        abort_unless(
            $notification->user_id ===
                $user->id,
            404
        );

        if (
            $notification->read_at ===
            null
        ) {
            $notification->read_at =
                now();

            $notification->save();
        }

        $unreadCount =
            Notification::query()
                ->where(
                    'user_id',
                    $user->id
                )
                ->whereNull(
                    'read_at'
                )
                ->count();

        return response()->json([
            'data' => [
                'notification' => [
                    'id' => $notification->id,

                    'read_at' => $notification
                        ->read_at,
                ],

                'unread_count' => $unreadCount,
            ],
        ]);
    }

    public function markAllAsRead(
        Request $request
    ): JsonResponse {
        $user =
            $request->user();

        Notification::query()
            ->where(
                'user_id',
                $user->id
            )
            ->whereNull(
                'read_at'
            )
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }
}
