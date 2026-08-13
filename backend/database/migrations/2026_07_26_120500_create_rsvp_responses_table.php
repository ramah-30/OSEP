<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guest responses from the public RSVP page. Kept append-only (a guest may change
 * their mind) — the latest row is the authoritative answer, mirrored onto the
 * guest's `rsvp_status` for fast reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rsvp_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('response');
            $table->unsignedTinyInteger('additional_guests')->default(0);
            $table->string('meal_choice')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('responded_at');
            $table->timestamps();

            $table->index(['event_id', 'response']);
            $table->index('guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rsvp_responses');
    }
};
