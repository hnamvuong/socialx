<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'conversation_members',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table
                    ->foreignId(
                        'conversation_id'
                    )
                    ->constrained(
                        'conversations'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'user_id'
                    )
                    ->constrained(
                        'users'
                    )
                    ->cascadeOnDelete();

                $table
                    ->string(
                        'role',
                        20
                    )
                    ->default(
                        'member'
                    );

                $table
                    ->timestamp(
                        'joined_at'
                    )
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'conversation_id',
                        'user_id',
                    ]
                );

                $table->index(
                    [
                        'user_id',
                        'conversation_id',
                    ]
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'conversation_members'
        );
    }
};
