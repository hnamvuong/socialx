<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table
                ->foreignId('parent_post_id')
                ->nullable()
                ->after('user_id')
                ->constrained('posts')
                ->nullOnDelete();

            $table
                ->foreignId('root_post_id')
                ->nullable()
                ->after('parent_post_id')
                ->constrained('posts')
                ->nullOnDelete();

            $table->index([
                'parent_post_id',
                'created_at',
            ]);

            $table->index([
                'root_post_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign([
                'parent_post_id',
            ]);

            $table->dropForeign([
                'root_post_id',
            ]);

            $table->dropIndex([
                'parent_post_id',
                'created_at',
            ]);

            $table->dropIndex([
                'root_post_id',
                'created_at',
            ]);

            $table->dropColumn([
                'parent_post_id',
                'root_post_id',
            ]);
        });
    }
};
