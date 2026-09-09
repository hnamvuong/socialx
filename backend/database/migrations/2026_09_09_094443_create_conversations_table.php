<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'conversations',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table
                    ->string(
                        'type',
                        20
                    )
                    ->default(
                        'direct'
                    );

                $table
                    ->string(
                        'title',
                        150
                    )
                    ->nullable();

                $table
                    ->foreignId(
                        'created_by'
                    )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'type',
                        'updated_at',
                    ]
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'conversations'
        );
    }
};
