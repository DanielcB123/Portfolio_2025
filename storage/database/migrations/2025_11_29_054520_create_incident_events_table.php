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
        Schema::create('incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('occurred_at')->index();
            $table->string('type')->index();  // detected, triage, mitigation, communication, resolved etc
            $table->string('actor')->nullable(); // who or what generated this
            $table->string('label');
            $table->text('detail')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_events');
    }
};
