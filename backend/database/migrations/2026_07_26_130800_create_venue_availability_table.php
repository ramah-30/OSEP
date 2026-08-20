<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained('marketplace_venues')->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('available'); // available | reserved | fully_booked | on_leave
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['venue_id', 'date']);
            $table->index(['venue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_availability');
    }
};
