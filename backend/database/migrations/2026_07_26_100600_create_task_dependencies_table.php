<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A task waits on the tasks it depends on. Kept as a pivot so a task can have
 * many dependencies without a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('event_tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('event_tasks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'depends_on_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
