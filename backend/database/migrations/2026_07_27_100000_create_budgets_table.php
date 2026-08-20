<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The master budget for an event. There is at most one per event; the three
 * totals capture the estimated / revised / final stages while a single status
 * drives the approval workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 8)->default('TZS');
            $table->decimal('estimated_total', 14, 2)->default(0);
            $table->decimal('revised_total', 14, 2)->nullable();
            $table->decimal('final_total', 14, 2)->nullable();
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
