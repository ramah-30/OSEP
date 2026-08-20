<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A vendor engaged for an event. `vendor_id` links a registered platform vendor
 * when there is one; `vendor_name` covers off-platform vendors the planner just
 * types in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors_assigned', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('vendor_name');
            $table->string('service');
            $table->string('package')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('status')->default('requested')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors_assigned');
    }
};
