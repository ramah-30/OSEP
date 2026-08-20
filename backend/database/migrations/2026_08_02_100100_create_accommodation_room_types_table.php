<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bookable room types within a hotel (e.g. "Honeymoon Suite", "Deluxe
 * Double"), each with a nightly rate, capacity and a small inventory count used
 * for availability checks when a stay is booked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained('accommodations')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_per_night', 12, 2);
            $table->string('currency', 8)->default('TZS');
            $table->unsignedSmallInteger('capacity')->default(2); // max guests
            $table->string('bed_configuration')->nullable();       // "1 King", "2 Twin"
            $table->unsignedSmallInteger('size_sqm')->nullable();
            $table->json('amenities')->nullable();                 // per-room extras
            $table->string('image_url')->nullable();
            $table->unsignedSmallInteger('total_rooms')->default(1); // inventory
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('accommodation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_room_types');
    }
};
