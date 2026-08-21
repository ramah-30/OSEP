<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews a client leaves about the planner who ran their event. Deliberately
 * lighter than the vendor {@see reviews} table - a single overall 1–5 rating and
 * a comment, no per-category scores, replies or moderation workflow. One review
 * per client per planner (re-submitting updates it).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // overall, 1–5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('planner_id');
            $table->unique(['planner_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_reviews');
    }
};
