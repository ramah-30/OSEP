<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A planner's request to hire a vendor or a venue. Exactly one of vendor_id /
 * venue_id is set; the pair keeps the two provider kinds in one queue without a
 * polymorphic column, which the dashboards and future AI matching read easily.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('marketplace_venues')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();

            $table->string('title')->nullable();
            $table->date('event_date')->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->text('requirements')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('response_note')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['venue_id', 'status']);
            $table->index(['planner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};
