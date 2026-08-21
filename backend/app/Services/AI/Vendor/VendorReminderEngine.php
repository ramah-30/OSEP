<?php

namespace App\Services\AI\Vendor;

use App\Models\User;

/**
 * Turns a vendor's live business snapshot into a prioritised list of reminder
 * cards - the next actions that win work and protect their rating. Pure, rule
 * based analysis over the {@see VendorContextBuilder} snapshot: it works offline
 * and never invents anything the data doesn't support.
 */
class VendorReminderEngine
{
    /** A booking request left this many days without a reply is flagged. */
    private const STALE_REQUEST_DAYS = 2;

    public function __construct(private readonly VendorContextBuilder $context) {}

    /**
     * @return array<int, array{key:string, category:string, priority:string, title:string, description:string, action_label:string, action_href:string}>
     */
    public function forVendor(User $vendor): array
    {
        return $this->fromContext($this->context->forVendor($vendor));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function fromContext(array $context): array
    {
        $out = [];
        $base = '/dashboard/vendor';

        // ---- Booking requests awaiting a reply ----------------------
        $r = $context['requests'] ?? null;
        if ($r && $r['open'] > 0) {
            $stale = $r['oldest_pending_days'] !== null && $r['oldest_pending_days'] >= self::STALE_REQUEST_DAYS;
            $out[] = [
                'key' => 'requests_open',
                'category' => 'requests',
                'priority' => $stale ? 'high' : 'medium',
                'title' => $r['open'] . ' booking request(s) awaiting your reply',
                'description' => $stale
                    ? "Your oldest is {$r['oldest_pending_days']} day(s) old - planners book fast, so reply promptly to win the work."
                    : 'Respond while the planner is still deciding to improve your win rate.',
                'action_label' => 'Open requests',
                'action_href' => "{$base}/requests",
            ];
        }

        // ---- Quotations expiring soon -------------------------------
        $q = $context['quotations'] ?? null;
        if ($q && $q['expiring_soon'] > 0) {
            $out[] = [
                'key' => 'quotes_expiring',
                'category' => 'quotations',
                'priority' => 'high',
                'title' => $q['expiring_soon'] . ' quotation(s) expiring within 7 days',
                'description' => 'Follow up with the planner before these lapse - a quick nudge often closes the deal.',
                'action_label' => 'Open quotations',
                'action_href' => "{$base}/quotations",
            ];
        }

        // ---- Unreplied reviews (negatives first) --------------------
        $rev = $context['reviews'] ?? null;
        if ($rev && $rev['unreplied_negative'] > 0) {
            $out[] = [
                'key' => 'reviews_negative',
                'category' => 'reviews',
                'priority' => 'high',
                'title' => $rev['unreplied_negative'] . ' critical review(s) need a reply',
                'description' => 'A calm, professional public reply to a low rating reassures future planners more than the review itself worries them.',
                'action_label' => 'Open reviews',
                'action_href' => "{$base}/reviews",
            ];
        } elseif ($rev && $rev['unreplied'] > 0) {
            $out[] = [
                'key' => 'reviews_unreplied',
                'category' => 'reviews',
                'priority' => 'low',
                'title' => $rev['unreplied'] . ' review(s) without a reply',
                'description' => 'Thanking reviewers publicly builds trust and signals an engaged, professional vendor.',
                'action_label' => 'Open reviews',
                'action_href' => "{$base}/reviews",
            ];
        }

        // ---- Contracts awaiting signature ---------------------------
        $c = $context['contracts'] ?? null;
        if ($c && $c['awaiting_signature'] > 0) {
            $out[] = [
                'key' => 'contracts_unsigned',
                'category' => 'contracts',
                'priority' => 'medium',
                'title' => $c['awaiting_signature'] . ' contract(s) awaiting signature',
                'description' => 'Unsigned contracts are revenue not yet secured. Chase the outstanding signatures to lock them in.',
                'action_label' => 'Open contracts',
                'action_href' => "{$base}/contracts",
            ];
        }

        // ---- Availability calendar not maintained -------------------
        $a = $context['availability'] ?? null;
        if ($a && (! $a['has_calendar'] || $a['available_upcoming'] === 0)) {
            $out[] = [
                'key' => 'availability_gap',
                'category' => 'availability',
                'priority' => 'medium',
                'title' => $a['has_calendar'] ? 'No open dates in the next 60 days' : 'Your availability calendar is empty',
                'description' => 'Planners filter by availability. Mark the dates you can take so you surface in more searches.',
                'action_label' => 'Update availability',
                'action_href' => "{$base}/availability",
            ];
        }

        // ---- Storefront completeness --------------------------------
        $s = $context['storefront'] ?? null;
        if ($s && ! empty($s['missing'])) {
            $missing = implode(', ', $s['missing']);
            $out[] = [
                'key' => 'storefront_incomplete',
                'category' => 'storefront',
                'priority' => 'medium',
                'title' => 'Your storefront is missing ' . count($s['missing']) . ' element(s)',
                'description' => "Add {$missing} - complete storefronts with photos and packages convert far more of the planners who view them.",
                'action_label' => 'Edit storefront',
                'action_href' => "{$base}/services",
            ];
        }

        // Order critical → high → medium → low.
        $rank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($out, fn ($a, $b) => ($rank[$a['priority']] ?? 9) <=> ($rank[$b['priority']] ?? 9));

        return $out;
    }
}
