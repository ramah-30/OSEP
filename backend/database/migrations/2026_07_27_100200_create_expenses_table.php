<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_assigned_id')->nullable()
                ->constrained('vendors_assigned')->nullOnDelete();
            $table->foreignId('budget_item_id')->nullable()
                ->constrained('budget_items')->nullOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 8)->default('TZS');
            $table->string('payment_method')->nullable();
            $table->string('status')->default('draft')->index();
            $table->date('expense_date');
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
