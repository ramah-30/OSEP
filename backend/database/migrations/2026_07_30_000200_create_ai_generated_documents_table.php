<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A document produced by the copilot from a template. `meta` records how it was
 * generated (driver, whether it was grounded in real event data) so AI content
 * stays distinguishable and auditable. Planners can edit `content` afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('ai_template_id')->nullable()->constrained('ai_templates')->nullOnDelete();
            $table->string('template_key')->nullable();     // catalog key if generated from a system template
            $table->string('category')->index();
            $table->string('title');
            $table->string('format')->default('markdown');
            $table->longText('content');
            $table->json('inputs')->nullable();             // variable values used at generation time
            $table->string('status')->default('draft');     // draft|final
            $table->string('model')->nullable();            // provider/model that produced it
            $table->json('meta')->nullable();               // {grounded, driver, event_id}
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generated_documents');
    }
};
