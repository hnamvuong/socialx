<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)
                ->nullable()
                ->unique()
                ->after('name');

            $table->string('display_name', 100)
                ->nullable()
                ->after('username');

            $table->text('bio')
                ->nullable()
                ->after('password');

            $table->string('location', 100)
                ->nullable()
                ->after('bio');

            $table->string('website', 2048)
                ->nullable()
                ->after('location');

            $table->string('avatar_path')
                ->nullable()
                ->after('website');

            $table->string('cover_path')
                ->nullable()
                ->after('avatar_path');

            $table->boolean('is_private')
                ->default(false)
                ->after('cover_path');

            $table->boolean('is_verified')
                ->default(false)
                ->after('is_private');

            $table->string('status', 30)
                ->default('active')
                ->index()
                ->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'username',
                'display_name',
                'bio',
                'location',
                'website',
                'avatar_path',
                'cover_path',
                'is_private',
                'is_verified',
                'status',
            ]);
        });
    }
};
