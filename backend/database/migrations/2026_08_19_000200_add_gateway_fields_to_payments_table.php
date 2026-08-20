<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields for the simulated mobile-money gateway: which contract an outgoing
 * (vendor) payment settles, which network was used, and the payer's own
 * number — none of these apply to a planner's manual ledger entry, so they
 * stay nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('invoice_id')
                ->constrained('contracts')->nullOnDelete();
            $table->string('network')->nullable()->after('method');
            $table->string('payer_phone')->nullable()->after('network');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
            $table->dropColumn(['network', 'payer_phone']);
        });
    }
};
