<?php

namespace App\Http\Controllers;

use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FollowController extends Controller
{
    public function follow(
        Request $request,
        User $user
    ): JsonResponse {
        $viewer =
            $request->user();

        $this->ensureTargetCanBeFollowed(
            $viewer,
            $user
        );

        /*
         * Nếu relationship đã tồn tại thì Follow API
         * phải idempotent.
         */
        $alreadyFollowing =
            DB::table('follows')
                ->where(
                    'follower_id',
                    $viewer->id
                )
                ->where(
                    'following_id',
                    $user->id
                )
                ->exists();

        if ($alreadyFollowing) {
            /*
             * Dọn dữ liệu stale nếu trước đó vì lý do nào đó
             * vẫn còn follow request.
             */
            DB::table('follow_requests')
                ->where(
                    'requester_id',
                    $viewer->id
                )
                ->where(
                    'target_id',
                    $user->id
                )
                ->delete();

            return response()->json([
                'data' => [
                    'relationship' => 'following',

                    'following' => true,

                    'follow_requested' => false,
                ],
            ]);
        }

        /*
         * PRIVATE ACCOUNT
         *
         * Không tạo follows ngay.
         * Chỉ tạo pending request.
         */
        if ($user->is_private) {
            DB::table('follow_requests')
                ->insertOrIgnore([
                    'requester_id' => $viewer->id,

                    'target_id' => $user->id,

                    'status' => FollowRequest::STATUS_PENDING,

                    'created_at' => now(),

                    'updated_at' => now(),
                ]);

            return response()->json([
                'data' => [
                    'relationship' => 'requested',

                    'following' => false,

                    'follow_requested' => true,
                ],
            ]);
        }

        /*
         * PUBLIC ACCOUNT
         *
         * Nếu trước đây account private và có request pending,
         * nhưng hiện đã chuyển public, xóa request stale.
         */
        DB::transaction(
            function () use (
                $viewer,
                $user
            ): void {
                DB::table(
                    'follow_requests'
                )
                    ->where(
                        'requester_id',
                        $viewer->id
                    )
                    ->where(
                        'target_id',
                        $user->id
                    )
                    ->delete();

                DB::table('follows')
                    ->insertOrIgnore([
                        'follower_id' => $viewer->id,

                        'following_id' => $user->id,

                        'created_at' => now(),

                        'updated_at' => now(),
                    ]);
            }
        );

        return response()->json([
            'data' => [
                'relationship' => 'following',

                'following' => true,

                'follow_requested' => false,
            ],
        ]);
    }

    public function unfollow(
        Request $request,
        User $user
    ): JsonResponse {
        $viewer =
            $request->user();

        /*
         * DELETE này đồng thời có nghĩa:
         *
         * following → unfollow
         * requested → cancel request
         *
         * Nếu không có gì thì vẫn 200.
         */
        DB::transaction(
            function () use (
                $viewer,
                $user
            ): void {
                DB::table('follows')
                    ->where(
                        'follower_id',
                        $viewer->id
                    )
                    ->where(
                        'following_id',
                        $user->id
                    )
                    ->delete();

                DB::table(
                    'follow_requests'
                )
                    ->where(
                        'requester_id',
                        $viewer->id
                    )
                    ->where(
                        'target_id',
                        $user->id
                    )
                    ->delete();
            }
        );

        return response()->json([
            'data' => [
                'relationship' => 'none',

                'following' => false,

                'follow_requested' => false,
            ],
        ]);
    }

    public function accept(
        Request $request,
        FollowRequest $followRequest
    ): JsonResponse {
        $viewer =
            $request->user();

        abort_unless(
            $followRequest->target_id
                === $viewer->id,
            403
        );

        $requester =
            $followRequest
                ->requester()
                ->firstOrFail();

        abort_if(
            $requester->status
                !== 'active',
            404
        );

        DB::transaction(
            function () use (
                $followRequest
            ): void {
                DB::table('follows')
                    ->insertOrIgnore([
                        'follower_id' => $followRequest
                            ->requester_id,

                        'following_id' => $followRequest
                            ->target_id,

                        'created_at' => now(),

                        'updated_at' => now(),
                    ]);

                DB::table(
                    'follow_requests'
                )
                    ->where(
                        'id',
                        $followRequest->id
                    )
                    ->delete();
            }
        );

        return response()->json([
            'data' => [
                'accepted' => true,

                'requester_id' => $followRequest
                    ->requester_id,

                'following' => true,
            ],
        ]);
    }

    public function reject(
        Request $request,
        FollowRequest $followRequest
    ): JsonResponse {
        $viewer =
            $request->user();

        abort_unless(
            $followRequest->target_id
                === $viewer->id,
            403
        );

        DB::table('follow_requests')
            ->where(
                'id',
                $followRequest->id
            )
            ->delete();

        return response()->json([
            'data' => [
                'rejected' => true,

                'requester_id' => $followRequest
                    ->requester_id,
            ],
        ]);
    }

    private function ensureTargetCanBeFollowed(
        User $viewer,
        User $target
    ): void {
        abort_if(
            $viewer->id ===
                $target->id,
            422,
            'Bạn không thể theo dõi chính mình.'
        );

        abort_if(
            $target->status
                !== 'active',
            404
        );
    }
}
