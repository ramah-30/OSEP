<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who sits where. Links a seat on a table object to a guest (when known) or a
 * free-text label such as "Reserved" via `notes`. Ties the Venue Designer to the
 * Guest Management module.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seating_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_object_id')->constrained('venue_objects')->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->unsignedSmallInteger('seat_number');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['venue_object_id', 'seat_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seating_assignments');
    }
};
