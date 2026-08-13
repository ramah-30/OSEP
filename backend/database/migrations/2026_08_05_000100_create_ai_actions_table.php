<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The AI action queue. When the copilot decides to *do* something on the
 * planner's behalf — send RSVP reminders, invite guests, create tasks, spin up
 * an event — it queues the action here instead of acting silently. The planner
 * approves (or rejects) each one; approval is what actually runs it. Rows are the
 * audit trail of everything the copilot has done or proposed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 20)->default('chat');   // chat | automation | manual
            $table->string('type', 40);                       // send_rsvp_reminders | send_invitations | create_tasks | create_event
            $table->string('title');
            $table->text('summary')->nullable();
            $table->json('params')->nullable();
            $table->string('status', 20)->default('pending'); // pending | done | failed | rejected
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_actions');
    }
};
