<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A guest's unique check-in credential. The `token` is what the QR image encodes
 * and what the scanner validates; `payload` snapshots the guest/event/ticket data
 * so a printed ticket stays meaningful offline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('ticket_type')->default('standard');
            $table->json('payload')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
