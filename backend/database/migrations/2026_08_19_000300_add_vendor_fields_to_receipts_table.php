<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An outgoing (vendor) payment's receipt needs a counterparty and a contract
 * to point at, mirroring how `client_id`/`invoice_id` work for incoming ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('invoice_id')
                ->constrained('contracts')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->after('client_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contract_id');
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
