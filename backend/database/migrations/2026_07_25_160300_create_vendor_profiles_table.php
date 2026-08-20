<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('verification_status')->default('pending')->index();
            $table->string('availability_status')->default('available')->index();

            // Business metrics surfaced on the vendor dashboard. Booking-driven
            // figures are seeded for now; Phase 3 recomputes them from real
            // bookings, views and reviews.
            $table->unsignedInteger('profile_views')->default(0);
            $table->unsignedInteger('pending_requests')->default(0);
            $table->unsignedInteger('completed_jobs')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
