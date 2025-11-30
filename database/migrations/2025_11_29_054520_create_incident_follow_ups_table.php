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
        Schema::create('incident_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('owner');    // team or person
            $table->string('label');
            $table->enum('status', ['open', 'in_progress', 'done'])->default('open')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_follow_ups');
    }
};
