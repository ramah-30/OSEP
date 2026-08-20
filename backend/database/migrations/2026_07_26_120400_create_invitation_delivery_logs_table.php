<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only delivery trail for an invitation — one entry per lifecycle event
 * (queued, sent, delivered, opened, failed). Powers the per-invitation history
 * and would be fed by a mail-provider webhook in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('channel')->nullable();
            $table->string('detail')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['invitation_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_delivery_logs');
    }
};
