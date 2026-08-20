<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable invitation designs. Global starters have a null owner; planners clone
 * or author their own. `theme` carries colours/typography as JSON so the editor
 * stays schema-free.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('custom');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('background_url')->nullable();
            $table->json('theme')->nullable();
            $table->date('rsvp_deadline')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['created_by', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_templates');
    }
};
