<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable tax / discount presets a planner can apply to quotations and
 * invoices. `type` is either a percentage or a fixed amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('kind')->default('tax');       // tax | discount
            $table->string('type')->default('percentage'); // percentage | fixed
            $table->decimal('rate', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rules');
    }
};
