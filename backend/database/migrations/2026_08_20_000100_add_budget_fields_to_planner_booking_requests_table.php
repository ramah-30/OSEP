<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `proposed_budget` is what the client suggests when they send the request;
 * `quoted_budget` is what the planner comes back with when accepting. If set,
 * the quote seeds the new event's Budget.estimated_total.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planner_booking_requests', function (Blueprint $table) {
            $table->decimal('proposed_budget', 12, 2)->nullable()->after('expected_guests');
            $table->decimal('quoted_budget', 12, 2)->nullable()->after('planner_note');
        });
    }

    public function down(): void
    {
        Schema::table('planner_booking_requests', function (Blueprint $table) {
            $table->dropColumn(['proposed_budget', 'quoted_budget']);
        });
    }
};
