<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured action items extracted from a meeting. Each can be pushed into the
 * event's real task board - `task_id` links the created event_task so the planner
 * can see (and avoid re-creating) items already actioned.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_meeting_id')->constrained('ai_meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('event_tasks')->nullOnDelete();
            $table->string('description', 500);
            $table->string('owner')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('open');   // open|done|dismissed
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['ai_meeting_id', 'status'], 'ai_meeting_items_meeting_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_meeting_action_items');
    }
};
