<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Captured meetings the copilot turns into a structured summary and action items.
 * The planner pastes raw notes/transcript; processing (offline analyzer or a live
 * model) produces the `summary` and rows in ai_meeting_action_items. event_id
 * null = a general meeting not tied to one event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title');
            $table->string('meeting_type')->default('client');   // client|vendor|internal|other
            $table->date('meeting_date')->nullable();
            $table->json('attendees')->nullable();               // ["Sarah", "Amina", ...]
            $table->longText('notes');                           // raw input
            $table->longText('summary')->nullable();             // AI-produced markdown
            $table->string('status')->default('captured');       // captured|processed
            $table->string('model')->nullable();                 // engine tag (local-analyzer / live driver)
            $table->json('meta')->nullable();                    // {grounded, driver, ...}
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_meetings');
    }
};
