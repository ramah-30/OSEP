<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files stored against an event (contracts, quotations, floor plans, images).
 * `task_id` optionally attaches a document to a task. `version` groups repeated
 * uploads of the same logical document for a foundational version history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('event_tasks')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('category')->default('other');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['event_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_documents');
    }
};
