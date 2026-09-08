<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'notifications',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('actor_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table
                    ->string('type', 50);

                $table
                    ->foreignId('post_id')
                    ->nullable()
                    ->constrained('posts')
                    ->cascadeOnDelete();

                $table
                    ->timestamp('read_at')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'user_id',
                        'created_at',
                    ],
                    'notifications_user_created_idx'
                );

                $table->index(
                    [
                        'user_id',
                        'read_at',
                    ],
                    'notifications_user_read_idx'
                );

                $table->index(
                    [
                        'actor_id',
                        'created_at',
                    ],
                    'notifications_actor_created_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'notifications'
        );
    }
};
