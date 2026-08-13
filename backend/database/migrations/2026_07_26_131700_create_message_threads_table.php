<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A conversation between a planner and a vendor (optionally about a venue and/or
 * an event). Messages hang off the thread; last_message_at drives the inbox
 * ordering and unread badges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('marketplace_venues')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('booking_request_id')->nullable()->constrained('booking_requests')->nullOnDelete();
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->index(['planner_id', 'last_message_at']);
            $table->index(['vendor_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_threads');
    }
};
