<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_event_health_scores');
    }

    public function down(): void
    {
        Schema::create('ai_event_health_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->integer('score');
            $table->string('label');
            $table->json('breakdown')->nullable();
            $table->json('forecasts')->nullable();
            $table->timestamp('computed_at');
            $table->timestamps();
        });
    }
};
