<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_hashtags', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table
                ->foreignId('hashtag_id')
                ->constrained('hashtags')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'post_id',
                    'hashtag_id',
                ],
                'post_hashtags_post_hashtag_unique'
            );

            $table->index(
                [
                    'hashtag_id',
                    'post_id',
                ],
                'post_hashtags_hashtag_post_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_hashtags');
    }
};
