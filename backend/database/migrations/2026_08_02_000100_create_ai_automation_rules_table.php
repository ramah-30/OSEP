<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planner-defined automation rules: "when <trigger> crosses <threshold> on an
 * event, have the copilot <action>" (raise a recommendation, draft a document,
 * or flag it). event_id null means the rule watches all of the planner's active
 * events.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_type');            // budget_over|tasks_overdue|rsvp_pending|vendor_unconfirmed|days_until|outstanding_invoices
            $table->decimal('threshold', 14, 2)->nullable();
            $table->string('action_type');             // recommend|draft_document|flag
            $table->json('action_config')->nullable(); // {template_key, priority, …}
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamp('last_fired_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_automation_rules');
    }
};
