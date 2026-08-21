<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planner feedback on AI output - a thumbs up/down (with an optional reason) on
 * an assistant message or a generated document. One row per planner per subject
 * (upserted), so the rating simply toggles. Feeds the AI quality view and makes
 * the assistant auditable and improvable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject_type');            // message|document
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('rating');                  // up|down
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feedback');
    }
};
