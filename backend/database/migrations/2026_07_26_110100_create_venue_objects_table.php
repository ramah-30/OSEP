<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single item placed on a layout (table, chair, stage, light …). `uid` is a
 * stable client-generated id so the canvas can bulk-save (upsert by uid) without
 * churning database ids and breaking seating references.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained('venue_layouts')->cascadeOnDelete();
            $table->string('uid', 40);
            $table->string('object_type');
            $table->string('object_name')->nullable();
            $table->decimal('x_position', 10, 2)->default(0);
            $table->decimal('y_position', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->default(0);
            $table->decimal('height', 10, 2)->default(0);
            $table->decimal('rotation', 6, 2)->default(0);
            $table->string('color')->nullable();
            $table->string('layer')->default('furniture');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->unique(['layout_id', 'uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_objects');
    }
};
