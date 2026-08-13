<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A confirmed room reservation a planner makes for a client — the honeymoon
 * stay. Price is snapshotted at booking time so later rate changes don't rewrite
 * history. Optionally tied to the client and their wedding event.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('accommodation_id')->constrained('accommodations')->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained('accommodation_room_types')->cascadeOnDelete();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('guest_name');           // name on the reservation
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedSmallInteger('rooms')->default(1);
            $table->unsignedSmallInteger('guests')->default(2);
            $table->decimal('price_per_night', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('currency', 8)->default('TZS');
            $table->string('status')->default('confirmed')->index();
            $table->text('special_requests')->nullable();
            $table->timestamps();

            $table->index(['planner_id', 'status']);
            $table->index(['room_type_id', 'check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_bookings');
    }
};
