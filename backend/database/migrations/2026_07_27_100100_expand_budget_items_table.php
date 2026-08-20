<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 6 enriches the Phase 3 budget line items with the fuller costing detail
 * the finance module needs: an approved figure, quantity/unit pricing, tax and
 * discount, plus a link back to the master budget.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->foreignId('budget_id')->nullable()->after('event_id')
                ->constrained('budgets')->nullOnDelete();
            $table->decimal('approved_cost', 12, 2)->default(0)->after('estimated_cost');
            $table->decimal('quantity', 10, 2)->default(1)->after('actual_cost');
            $table->decimal('unit_cost', 12, 2)->default(0)->after('quantity');
            $table->decimal('tax', 12, 2)->default(0)->after('unit_cost');
            $table->decimal('discount', 12, 2)->default(0)->after('tax');
            $table->text('notes')->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('budget_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('budget_id');
            $table->dropColumn(['approved_cost', 'quantity', 'unit_cost', 'tax', 'discount', 'notes']);
        });
    }
};
