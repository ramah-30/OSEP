<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The activity log of automation rules that fired - what condition was met, on
 * which event, and what the copilot did about it (with a pointer to the created
 * recommendation or document). Also serves as the dedupe window so a rule does
 * not re-fire on every evaluation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_automation_rule_id')->constrained('ai_automation_rules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('summary');
            $table->string('action_type');
            $table->string('result_type')->nullable();  // recommendation|document
            $table->unsignedBigInteger('result_id')->nullable();
            $table->timestamps();

            $table->index(['ai_automation_rule_id', 'event_id', 'created_at'], 'ai_auto_runs_rule_event_idx');
            $table->index(['user_id', 'created_at'], 'ai_auto_runs_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_automation_runs');
    }
};
