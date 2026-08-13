<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The catalogue of meal options a guest can pick on the RSVP page, per event.
 * RSVP responses store the chosen option by name; this table drives the dropdown
 * and the "meal preferences" breakdown chart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_meal_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('dietary_tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_meal_preferences');
    }
};
