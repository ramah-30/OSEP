<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The AI template library. System templates (user_id null, is_system true) are
 * seeded idempotently by `key` from the DocumentTemplateCatalog; planners may
 * also save their own. Each template is a reusable blueprint the copilot fills
 * with real event data to generate a document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('key')->nullable();          // stable slug for seeded system templates
            $table->string('category')->index();        // proposal|timeline|checklist|email|vendor|budget|speech|social
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->default('FileText');
            $table->string('output_format')->default('markdown');
            $table->longText('body_template')->nullable(); // instruction/scaffold used by the live LLM path
            $table->json('variables')->nullable();         // [{key,label,type,required,placeholder}]
            $table->boolean('requires_event')->default(false);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('key');
            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_templates');
    }
};
