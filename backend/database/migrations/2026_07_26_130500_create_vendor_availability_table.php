<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('available'); // available | reserved | fully_booked | on_leave
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'date']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_availability');
    }
};
