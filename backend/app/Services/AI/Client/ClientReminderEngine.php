<?php

namespace App\Services\AI\Client;

use App\Models\User;

/**
 * Turns a client's snapshot into a short, prioritised list of things that need
 * their attention - an approval to give, a payment due, guests still to chase.
 * Pure rule-based analysis over the {@see ClientContextBuilder} snapshot: works
 * offline and only ever reflects the client's real data.
 */
class ClientReminderEngine
{
    public function __construct(private readonly ClientContextBuilder $context) {}

    /**
     * @return array<int, array{key:string, category:string, priority:string, title:string, description:string, action_label:string, action_href:string}>
     */
    public function forClient(User $client): array
    {
        return $this->fromContext($this->context->forClient($client));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function fromContext(array $context): array
    {
        $out = [];
        $base = '/dashboard/client';

        // ---- Approvals awaiting the client's decision ---------------
        $a = $context['approvals'] ?? null;
        if ($a && $a['pending'] > 0) {
            $out[] = [
                'key' => 'approvals_pending',
                'category' => 'approvals',
                'priority' => 'high',
                'title' => $a['pending'] . ' approval(s) waiting for you',
                'description' => 'Your planner needs your decision to keep things moving. Review and respond so nothing stalls.',
                'action_label' => 'Review approvals',
                'action_href' => "{$base}/my-events",
            ];
        }

        // ---- Payments due -------------------------------------------
        $f = $context['finance'] ?? null;
        if ($f && $f['outstanding_amount'] > 0) {
            $overdue = ($f['overdue_count'] ?? 0) > 0;
            $out[] = [
                'key' => 'invoices_outstanding',
                'category' => 'finance',
                'priority' => $overdue ? 'high' : 'medium',
                'title' => $overdue
                    ? $f['overdue_count'] . ' payment(s) overdue'
                    : 'TZS ' . number_format($f['outstanding_amount'], 0) . ' outstanding',
                'description' => $f['next_due_date']
                    ? "Your next payment is due {$f['next_due_date']}. Settling on time keeps your planning on track."
                    : 'You have an outstanding balance with your planner.',
                'action_label' => 'View budget',
                'action_href' => "{$base}/budget",
            ];
        }

        // ---- Guests still to RSVP -----------------------------------
        $g = $context['guests'] ?? null;
        if ($g && $g['total'] > 0 && $g['pending'] > 0) {
            $out[] = [
                'key' => 'guests_pending',
                'category' => 'guests',
                'priority' => 'medium',
                'title' => $g['pending'] . ' guest(s) haven’t RSVP’d',
                'description' => 'A friendly nudge now firms up your headcount for catering and seating.',
                'action_label' => 'Open guest list',
                'action_href' => "{$base}/guests",
            ];
        }

        // ---- A planner responded to a booking request ---------------
        $r = $context['requests'] ?? null;
        if ($r && ($r['accepted'] ?? 0) > 0) {
            $out[] = [
                'key' => 'request_accepted',
                'category' => 'requests',
                'priority' => 'medium',
                'title' => 'A planner accepted your booking request',
                'description' => 'Great news - review their response and take the next step to lock in your planner.',
                'action_label' => 'View requests',
                'action_href' => "{$base}/booking-requests",
            ];
        }

        // ---- Event approaching --------------------------------------
        $event = $context['event'] ?? null;
        if ($event && isset($event['days_until']) && $event['days_until'] !== null
            && $event['days_until'] >= 0 && $event['days_until'] <= 14) {
            $out[] = [
                'key' => 'event_soon',
                'category' => 'event',
                'priority' => 'low',
                'title' => "Your event is {$event['days_until']} day(s) away",
                'description' => "Final stretch for {$event['title']}. Check your outstanding approvals, payments and RSVPs.",
                'action_label' => 'Open my event',
                'action_href' => "{$base}/my-events",
            ];
        }

        $rank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($out, fn ($a, $b) => ($rank[$a['priority']] ?? 9) <=> ($rank[$b['priority']] ?? 9));

        return $out;
    }
}
