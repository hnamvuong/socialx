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
                ->foreignId('quoted_post_id')
                ->nullable()
                ->after('root_post_id')
                ->constrained('posts')
                ->nullOnDelete();

            $table->index([
                'quoted_post_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign([
                'quoted_post_id',
            ]);

            $table->dropIndex([
                'quoted_post_id',
                'created_at',
            ]);

            $table->dropColumn(
                'quoted_post_id'
            );
        });
    }
};
