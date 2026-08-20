<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin verification for planner and client accounts, mirroring how vendors are
 * verified in the marketplace. A non-null `verified_at` marks the account as
 * reviewed and approved by an administrator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planner_profiles', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('website');
        });

        Schema::table('client_profiles', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('planner_profiles', function (Blueprint $table) {
            $table->dropColumn('verified_at');
        });

        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropColumn('verified_at');
        });
    }
};
