<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planner_profiles', function (Blueprint $table) {
            $table->string('booking_slug')->unique()->nullable()->after('website');
        });

        Schema::table('users', function (Blueprint $table) {
            // false = planner created an offline account on the client's behalf
            $table->boolean('account_claimed')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('planner_profiles', function (Blueprint $table) {
            $table->dropColumn('booking_slug');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_claimed');
        });
    }
};
