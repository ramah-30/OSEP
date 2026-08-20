<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A recorded money movement. `direction` splits incoming client payments (paid
 * against an invoice) from outgoing vendor payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('vendor_assigned_id')->nullable()
                ->constrained('vendors_assigned')->nullOnDelete();
            $table->foreignId('payment_schedule_id')->nullable()
                ->constrained('payment_schedules')->nullOnDelete();
            $table->string('direction')->default('incoming')->index();
            $table->string('party_name')->nullable();
            $table->string('method')->default('mobile_money');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 8)->default('TZS');
            $table->string('transaction_ref')->nullable();
            $table->string('reference')->nullable();
            $table->string('status')->default('completed')->index();
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['planner_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
