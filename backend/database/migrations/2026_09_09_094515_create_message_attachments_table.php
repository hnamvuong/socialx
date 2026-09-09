<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'message_attachments',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table
                    ->foreignId(
                        'message_id'
                    )
                    ->constrained(
                        'messages'
                    )
                    ->cascadeOnDelete();

                $table
                    ->string(
                        'type',
                        20
                    );

                $table
                    ->string(
                        'path'
                    );

                $table
                    ->string(
                        'mime_type',
                        100
                    )
                    ->nullable();

                $table
                    ->unsignedBigInteger(
                        'size'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'width'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'height'
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(0);

                $table->timestamps();

                $table->index(
                    [
                        'message_id',
                        'sort_order',
                    ]
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'message_attachments'
        );
    }
};
