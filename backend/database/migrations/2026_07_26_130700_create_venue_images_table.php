<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('marketplace_venues')->cascadeOnDelete();
            $table->string('url');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('venue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_images');
    }
};
