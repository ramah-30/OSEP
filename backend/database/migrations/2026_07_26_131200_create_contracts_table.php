<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('booking_request_id')->nullable()->constrained('booking_requests')->nullOnDelete();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('marketplace_venues')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();

            $table->string('reference')->unique();
            $table->string('title')->nullable();
            $table->string('status')->default('draft')->index();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency')->default('TZS');
            $table->text('terms')->nullable();
            $table->string('document_path')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['planner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
