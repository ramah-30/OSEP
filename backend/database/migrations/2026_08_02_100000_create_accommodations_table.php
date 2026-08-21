<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hotels / accommodation listings - a marketplace vertical distinct from event
 * venues. A planner browses these to book a stay for a client (e.g. a honeymoon
 * after the wedding). Rooms hang off {@see accommodation_room_types}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('star_rating')->nullable(); // hotel class, 1–5
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->json('amenities')->nullable();       // ['Pool','Spa','Free Wi-Fi',...]
            $table->string('cover_image_url')->nullable();
            $table->json('gallery')->nullable();
            $table->string('currency', 8)->default('TZS');
            $table->decimal('price_from', 12, 2)->default(0); // cheapest nightly, for cards
            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->text('policies')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('profile_views')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
