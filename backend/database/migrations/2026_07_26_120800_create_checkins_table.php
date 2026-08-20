<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arrival record. One per guest (unique) so a duplicate scan is rejected rather
 * than double-counting attendance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qr_code_id')->nullable()->constrained('qr_codes')->nullOnDelete();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method')->default('manual');
            $table->unsignedTinyInteger('party_size')->default(1);
            $table->string('notes')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamps();

            $table->unique('guest_id');
            $table->index(['event_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
