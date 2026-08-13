<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_assigned_id')->nullable()
                ->constrained('vendors_assigned')->nullOnDelete();
            $table->string('category');
            $table->string('description');
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->string('status')->default('planned')->index();
            $table->timestamps();

            $table->index(['event_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
