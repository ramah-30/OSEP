<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per invitation issued to a guest on a given channel. The public RSVP
 * link uses the guest's stable token, so the invitation itself just tracks the
 * send lifecycle (draft → scheduled → sent → delivered → opened / failed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('invitation_templates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel')->default('email');
            $table->string('status')->default('draft')->index();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('failed_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['guest_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
