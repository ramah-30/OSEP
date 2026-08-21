<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The planner's reusable prompt library. Each template is a saved, named prompt
 * (with {{variables}}) that can be run against an event's live data. Bodies are
 * versioned in ai_prompt_versions; `current_version` points at the active one so
 * edits are auditable and any earlier version can be rolled back to.
 * event_id null = a general prompt usable on any event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('description')->nullable();
            $table->json('variables')->nullable();        // ["client_name", "budget", ...] parsed from the body
            $table->unsignedInteger('current_version')->default(1);
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('pinned')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pinned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_templates');
    }
};
