<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A saved version of a venue floor plan for an event. An event can hold several
 * (Wedding Layout, Reception Layout ...); each carries its own venue dimensions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('layout_name');
            $table->string('venue_name')->nullable();
            $table->string('venue_type')->nullable();
            $table->string('setting')->nullable(); // indoor | outdoor | mixed
            $table->decimal('width', 8, 2)->default(0);
            $table->decimal('height', 8, 2)->default(0); // "length" in the UI
            $table->string('unit', 8)->default('m');
            $table->unsignedInteger('max_capacity')->nullable();
            $table->unsignedSmallInteger('entry_points')->nullable();
            $table->unsignedSmallInteger('exit_points')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->json('meta')->nullable(); // layers, grid/snap prefs
            $table->timestamps();

            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_layouts');
    }
};
