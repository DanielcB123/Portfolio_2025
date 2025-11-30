<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // team relation
            $table->foreignId('team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete()
                ->after('id');

            // UI fields
            $table->string('avatar_color')->nullable()->after('team_id');
            $table->boolean('is_online')->default(false)->after('avatar_color');
            $table->timestamp('last_seen_at')->nullable()->after('is_online');

            // API key fields
            $table->timestamp('api_key_expires_at')->nullable()->after('api_key');
            $table->timestamp('api_key_last_used_at')->nullable()->after('api_key_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);

            $table->dropColumn([
                'team_id',
                'avatar_color',
                'is_online',
                'last_seen_at',
                'api_key_expires_at',
                'api_key_last_used_at',
            ]);
        });
    }
};
