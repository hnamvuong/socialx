<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'mentions',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('post_id')
                    ->constrained('posts')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('mentioned_user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'post_id',
                        'mentioned_user_id',
                    ],
                    'mentions_post_user_unique'
                );

                $table->index(
                    [
                        'mentioned_user_id',
                        'created_at',
                    ],
                    'mentions_user_created_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'mentions'
        );
    }
};
