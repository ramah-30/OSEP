<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent AI memory. Two scopes:
 *  - planner: reusable preferences (timeline style, tone, report format ...)
 *  - event:   facts about one event (theme, client preferences, decisions),
 *             which expire when the event is archived unless promoted.
 * Planners can view, edit and delete every memory.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->string('scope')->default('planner'); // planner|event
            $table->string('label');
            $table->text('value');
            $table->boolean('pinned')->default(false);   // promoted event memory survives archive
            $table->string('source')->default('manual'); // manual|inferred
            $table->timestamps();

            $table->index(['user_id', 'scope']);
            $table->index(['event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_memories');
    }
};
