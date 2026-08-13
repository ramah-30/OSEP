<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flags the activity-feed rows the client is allowed to see. Most planner
     * actions stay internal; the handful of client-facing milestones (a vendor
     * confirming, a contract signed, a quotation sent) set this true, which also
     * drives the notification ping to the event's client.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->boolean('visible_to_client')->default(false)->after('description');
            $table->index(['event_id', 'visible_to_client', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'visible_to_client', 'created_at']);
            $table->dropColumn('visible_to_client');
        });
    }
};
