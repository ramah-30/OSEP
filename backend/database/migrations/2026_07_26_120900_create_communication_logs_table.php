<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-guest communication history — invitations, reminders, RSVP responses,
 * planner notes and check-ins in one timeline. `guest_id` is nullable so an
 * event-wide broadcast can be logged too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('channel')->nullable();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
            $table->index(['guest_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
