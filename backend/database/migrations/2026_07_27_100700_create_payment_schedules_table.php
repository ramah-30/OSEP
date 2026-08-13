<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Installment plans (e.g. 30% deposit / 40% mid / 30% before event) attached to
 * an invoice or a vendor. Actual money movements are recorded as `payments`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('vendor_assigned_id')->nullable()
                ->constrained('vendors_assigned')->nullOnDelete();
            $table->string('label');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 8)->default('TZS');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending')->index();
            $table->date('paid_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
