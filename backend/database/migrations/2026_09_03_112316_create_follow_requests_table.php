<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_requests', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('requester_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table
                ->foreignId('target_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table
                ->string('status', 20)
                ->default('pending');

            $table->timestamps();

            $table->unique([
                'requester_id',
                'target_id',
            ]);

            $table->index([
                'target_id',
                'status',
                'created_at',
            ]);

            $table->index([
                'requester_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'follow_requests'
        );
    }
};
