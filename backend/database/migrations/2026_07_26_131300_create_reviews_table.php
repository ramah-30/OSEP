<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('marketplace_venues')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();

            // Per-category 1–5 scores; overall is their average, stored for sorting.
            $table->unsignedTinyInteger('rating_professionalism')->nullable();
            $table->unsignedTinyInteger('rating_communication')->nullable();
            $table->unsignedTinyInteger('rating_quality')->nullable();
            $table->unsignedTinyInteger('rating_value')->nullable();
            $table->unsignedTinyInteger('rating_timeliness')->nullable();
            $table->decimal('overall_rating', 3, 2);

            $table->string('title')->nullable();
            $table->text('comment')->nullable();
            $table->string('status')->default('published')->index();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['venue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
