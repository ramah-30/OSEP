<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An immutable trail of every action taken on an approval (submitted, approved,
 * rejected, changes requested), so the decision history is never lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('approvals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_history');
    }
};
