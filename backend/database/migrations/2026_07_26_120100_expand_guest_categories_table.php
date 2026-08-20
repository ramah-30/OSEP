<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give guest categories the colour / priority / default seating attributes the
 * Phase 4 spec calls for. Existing rows keep sensible defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_categories', function (Blueprint $table) {
            $table->string('color', 20)->default('#64748b')->after('name');
            $table->unsignedTinyInteger('priority')->default(3)->after('color');
            $table->string('default_seating_area')->nullable()->after('priority');
            $table->boolean('is_default')->default(false)->after('default_seating_area');
        });
    }

    public function down(): void
    {
        Schema::table('guest_categories', function (Blueprint $table) {
            $table->dropColumn(['color', 'priority', 'default_seating_area', 'is_default']);
        });
    }
};
