<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_automation_runs');
        Schema::dropIfExists('ai_automation_rules');
        Schema::dropIfExists('ai_recommendations');
    }

    public function down(): void
    {
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('signature')->unique();
            $table->string('category');
            $table->string('priority');
            $table->integer('confidence')->default(80);
            $table->text('title');
            $table->longText('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('event_id');
        });

        Schema::create('ai_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('name');
            $table->string('trigger_type');
            $table->json('trigger_config')->nullable();
            $table->string('action_type');
            $table->json('action_config')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('ai_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_automation_rule_id')->constrained('ai_automation_rules')->cascadeOnDelete();
            $table->string('status');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index('ai_automation_rule_id');
        });

        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->longText('body');
            $table->json('variables')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_prompt_template_id')->constrained('ai_prompt_templates')->cascadeOnDelete();
            $table->integer('version');
            $table->longText('body');
            $table->text('change_log')->nullable();
            $table->timestamps();
            $table->unique(['ai_prompt_template_id', 'version']);
        });
    }
};
