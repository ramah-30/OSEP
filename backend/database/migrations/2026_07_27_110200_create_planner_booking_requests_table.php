<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_booking_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();    // BKR-2026-0001
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type')->nullable();
            $table->date('event_date')->nullable();
            $table->unsignedInteger('expected_guests')->nullable();
            $table->string('venue')->nullable();
            $table->string('location')->nullable();
            $table->text('message')->nullable();       // client's initial message
            $table->string('status')->default('pending'); // pending/accepted/declined/withdrawn
            $table->text('planner_note')->nullable();  // planner's response note
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_booking_requests');
    }
};
