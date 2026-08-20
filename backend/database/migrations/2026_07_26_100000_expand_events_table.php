<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grows the lean Phase 2 event scaffold into the full record the Phase 3 event
 * engine needs: a human-friendly code, scheduling detail, guest estimate,
 * priority and free-text notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_code')->nullable()->unique()->after('id');
            $table->string('event_category')->nullable()->after('event_type');
            $table->time('start_time')->nullable()->after('event_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->unsignedInteger('expected_guests')->nullable()->after('location');
            $table->text('description')->nullable()->after('expected_guests');
            $table->string('theme')->nullable()->after('description');
            $table->string('priority')->default('medium')->after('theme');
            $table->text('internal_notes')->nullable()->after('priority');

            $table->index(['planner_id', 'status']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['planner_id', 'status']);
            $table->dropIndex(['event_date']);
            $table->dropColumn([
                'event_code', 'event_category', 'start_time', 'end_time',
                'expected_guests', 'description', 'theme', 'priority', 'internal_notes',
            ]);
        });
    }
};
