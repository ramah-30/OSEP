<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A planner's named shortlist ("Wedding Vendors", "Luxury Suppliers"). Items are
 * held in saved_items and may point at either a vendor or a venue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('planner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_collections');
    }
};
