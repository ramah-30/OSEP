<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest reviews of a hotel / accommodation - the lighter single-rating flow
 * (like planner_reviews): one overall 1–5 rating and a comment, no categories or
 * replies. One review per reviewer per hotel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained('accommodations')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // overall, 1–5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index('accommodation_id');
            $table->unique(['accommodation_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_reviews');
    }
};
