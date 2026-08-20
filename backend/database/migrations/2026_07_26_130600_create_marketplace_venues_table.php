<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace venue listings. Deliberately NOT the Phase 3 `venues` table, which
 * is the single venue attached to one event — this is a public, searchable
 * directory of bookable venues owned by vendor-role users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_venues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('venue_type')->nullable();
            $table->text('description')->nullable();
            $table->string('setting')->default('indoor'); // indoor | outdoor | both

            // Capacity & space
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('min_capacity')->nullable();
            $table->string('dimensions')->nullable();
            $table->json('layout_options')->nullable();      // array of strings
            $table->string('setup_time')->nullable();
            $table->string('breakdown_time')->nullable();
            $table->json('included_equipment')->nullable();  // array of strings
            $table->json('facilities')->nullable();          // array of strings
            $table->json('accessibility')->nullable();       // array of strings
            $table->text('restrictions')->nullable();

            // Parking & access
            $table->boolean('parking_available')->default(false);
            $table->unsignedInteger('parking_capacity')->nullable();

            // Pricing
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency')->default('TZS');
            $table->string('price_unit')->nullable();        // e.g. "per day"

            // Location & contact
            $table->string('address')->nullable();
            $table->string('location')->nullable();          // city / region for filtering
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('booking_terms')->nullable();

            // Presentation & trust
            $table->string('cover_image_url')->nullable();
            $table->string('verification_level')->default('unverified')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_suspended')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();

            // Metrics
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('profile_views')->default(0);

            $table->timestamps();

            $table->index(['setting', 'is_published']);
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_venues');
    }
};
