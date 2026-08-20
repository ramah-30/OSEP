<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A contract's payment progress, tracked separately from its legal `status`
 * (draft/sent/signed/active/completed) — a signed, active contract can be
 * partially paid without that overwriting where it is in its own lifecycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('amount_paid', 12, 2)->default(0)->after('amount');
            $table->string('payment_status')->default('unpaid')->index()->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_status']);
        });
    }
};
