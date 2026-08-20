<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestones double as the event timeline in Phase 3, so they gain a
 * description, a deadline, a reminder and a responsible user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_milestones', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->date('due_date')->nullable()->after('status');
            $table->timestamp('reminder_at')->nullable()->after('due_date');
            $table->foreignId('assigned_to')->nullable()->after('reminder_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_milestones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['description', 'due_date', 'reminder_at']);
        });
    }
};
