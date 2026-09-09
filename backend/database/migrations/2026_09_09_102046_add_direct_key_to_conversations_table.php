<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'conversations',
            function (
                Blueprint $table
            ): void {
                $table
                    ->string(
                        'direct_key',
                        100
                    )
                    ->nullable()
                    ->unique()
                    ->after('type');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'conversations',
            function (
                Blueprint $table
            ): void {
                $table->dropUnique([
                    'direct_key',
                ]);

                $table->dropColumn(
                    'direct_key'
                );
            }
        );
    }
};
