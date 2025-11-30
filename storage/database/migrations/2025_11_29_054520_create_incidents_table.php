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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // IC-2471
            $table->string('title');
            $table->enum('severity', ['SEV1', 'SEV2', 'SEV3'])->index();
            $table->enum('status', ['investigating', 'mitigating', 'monitoring', 'resolved'])->index();
            $table->string('system')->index();        // Payments, Analytics, Auth etc
            $table->json('impacted_regions')->nullable();
            $table->string('impacted_users')->nullable();
            $table->string('owner')->nullable();      // On call, team, etc
            $table->text('summary')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('started_at')->index();
            $table->timestamp('last_updated_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
