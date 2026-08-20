<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The catalogue of event types shown in the create-event form. Global defaults
 * are seeded with a null owner; a planner can add their own (created_by set),
 * which only they see.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['created_by', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};
