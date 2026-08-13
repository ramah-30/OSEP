<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Budget line categories (Venue, Catering, Decoration …). Seeded defaults have
 * a null owner; planners may add custom categories scoped to themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['created_by', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
    }
};
