<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A thread between a planner and the AI copilot. May be tied to a specific
 * event (context_type = event) or free-standing (general / budget / vendor),
 * and can be pinned and grouped into folders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('title')->default('New conversation');
            $table->string('context_type')->default('general'); // general|event|budget|vendor
            $table->string('folder')->nullable();
            $table->boolean('pinned')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pinned', 'last_message_at']);
            $table->index(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
