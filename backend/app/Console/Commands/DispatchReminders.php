<?php

namespace App\Console\Commands;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Services\InvitationDispatcher;
use Illuminate\Console\Command;

/**
 * Delivers any invitation or reminder whose scheduled time has arrived. Point a
 * cron entry at `php artisan schedule:run` (which runs this every minute - see
 * routes/console.php) or call this command directly.
 */
class DispatchReminders extends Command
{
    protected $signature = 'osep:dispatch-reminders';

    protected $description = 'Send scheduled invitations and reminders that are now due.';

    public function handle(InvitationDispatcher $dispatcher): int
    {
        $due = Invitation::where('status', InvitationStatus::Scheduled->value)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled sends are due.');

            return self::SUCCESS;
        }

        foreach ($due as $invitation) {
            $dispatcher->dispatch($invitation);
            $this->line("Dispatched #{$invitation->id} to guest #{$invitation->guest_id}");
        }

        $this->info("Dispatched {$due->count()} scheduled send(s).");

        return self::SUCCESS;
    }
}
