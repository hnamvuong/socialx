<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();

            $table
                ->string('type', 20)
                ->default('image');

            $table->string('path');

            $table
                ->string('mime_type', 100)
                ->nullable();

            $table
                ->unsignedInteger('width')
                ->nullable();

            $table
                ->unsignedInteger('height')
                ->nullable();

            $table
                ->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->unique([
                'post_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_media');
    }
};
