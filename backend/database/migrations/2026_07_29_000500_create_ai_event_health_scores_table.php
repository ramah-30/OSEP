<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A cached Event Health Score (0–100) with the component breakdown it was
 * derived from (budget, timeline, vendor, guest, approvals). Recomputed when
 * stale so the analytics dashboard stays cheap to render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_event_health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->string('label')->default('Unknown');
            $table->json('breakdown'); // per-component score + weight + note
            $table->json('forecasts')->nullable(); // predictive figures + confidence
            $table->timestamp('computed_at');
            $table->timestamps();

            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_event_health_scores');
    }
};
