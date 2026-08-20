<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only version history for a prompt template. Every edit (and every
 * rollback) writes a new row with an incrementing `version` number, so the full
 * lineage of a prompt is preserved and auditable. The template's active body is
 * always the highest version number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_prompt_template_id')->constrained('ai_prompt_templates')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->longText('body');
            $table->string('note')->nullable();           // changelog, e.g. "Tightened tone" / "Rolled back to v2"
            $table->timestamps();

            $table->unique(['ai_prompt_template_id', 'version'], 'ai_prompt_versions_tpl_ver_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_versions');
    }
};
