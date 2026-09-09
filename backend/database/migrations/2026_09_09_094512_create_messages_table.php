<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'messages',
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
                        'sender_id'
                    )
                    ->constrained(
                        'users'
                    )
                    ->cascadeOnDelete();

                $table
                    ->text(
                        'body'
                    )
                    ->nullable();

                $table->timestamps();

                $table->index(
                    [
                        'conversation_id',
                        'id',
                    ]
                );

                $table->index(
                    [
                        'sender_id',
                        'created_at',
                    ]
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'messages'
        );
    }
};
