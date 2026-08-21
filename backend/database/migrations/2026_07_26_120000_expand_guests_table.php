<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 - grow the lean Phase 3 guest record into a full profile: split name,
 * demographics, accessibility, the separate invitation / check-in status tracks,
 * plus-one allowance and a stable public RSVP token.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('event_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('gender')->nullable()->after('email');
            $table->text('dietary_restrictions')->nullable()->after('meal_preference');
            $table->text('accessibility_requirements')->nullable()->after('dietary_restrictions');
            $table->unsignedTinyInteger('plus_ones_allowed')->default(0)->after('accessibility_requirements');
            $table->string('invitation_status')->default('draft')->index()->after('rsvp_status');
            $table->string('checkin_status')->default('pending')->index()->after('invitation_status');
            $table->timestamp('rsvp_responded_at')->nullable()->after('checkin_status');
            $table->timestamp('checked_in_at')->nullable()->after('rsvp_responded_at');
            $table->timestamp('archived_at')->nullable()->after('checked_in_at');
            $table->string('rsvp_token', 64)->nullable()->unique()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'gender', 'dietary_restrictions',
                'accessibility_requirements', 'plus_ones_allowed', 'invitation_status',
                'checkin_status', 'rsvp_responded_at', 'checked_in_at', 'archived_at', 'rsvp_token',
            ]);
        });
    }
};
