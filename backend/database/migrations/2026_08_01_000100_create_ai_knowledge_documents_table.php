<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The planner's knowledge base: reusable planning notes, policies and playbooks
 * (event_id null = global) or facts scoped to one event. The copilot retrieves
 * the passages relevant to a question and cites them in its grounded answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();    // freeform: Policy, Checklist, Vendor notes, …
            $table->longText('content');
            $table->boolean('pinned')->default(false);
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_documents');
    }
};
