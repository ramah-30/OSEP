<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lean event scaffold. Phase 2 only reads these (seeded) to power the
     * client workspace and dashboard stats; the full event-management module,
     * with planner-side CRUD, lands in Phase 3.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('event_type')->nullable();
            $table->date('event_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('planning')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->decimal('budget_total', 12, 2)->default(0);
            $table->decimal('budget_spent', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
