<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->index(
                [
                    'user_id',
                    'parent_post_id',
                    'created_at',
                ],
                'posts_feed_author_parent_created_idx'
            );
        });

        Schema::table('likes', function (Blueprint $table): void {
            $table->index(
                'post_id',
                'likes_post_id_idx'
            );
        });

        Schema::table('reposts', function (Blueprint $table): void {
            $table->index(
                'post_id',
                'reposts_post_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(
                'posts_feed_author_parent_created_idx'
            );
        });

        Schema::table('likes', function (Blueprint $table): void {
            $table->dropIndex(
                'likes_post_id_idx'
            );
        });

        Schema::table('reposts', function (Blueprint $table): void {
            $table->dropIndex(
                'reposts_post_id_idx'
            );
        });
    }
};
