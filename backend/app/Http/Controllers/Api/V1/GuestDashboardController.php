<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CheckinStatus;
use App\Enums\RsvpStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aggregates for the Guest Dashboard, RSVP Dashboard and Check-in Dashboard.
 * Everything is computed in PHP over eager-loaded rows so the figures are
 * consistent and database-agnostic.
 */
class GuestDashboardController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $guests = $event->guests()->whereNull('archived_at')->get();
        $active = $guests->count();

        $confirmed = $guests->where('rsvp_status', RsvpStatus::Confirmed);
        $declined = $guests->where('rsvp_status', RsvpStatus::Declined);
        $maybe = $guests->where('rsvp_status', RsvpStatus::Maybe);
        $pending = $guests->whereIn('rsvp_status', RsvpStatus::pendingStates());
        $invitedCount = $guests->where('invitation_status', '!=', \App\Enums\InvitationStatus::Draft)->count();
        $respondedCount = $confirmed->count() + $declined->count() + $maybe->count();
        $checkedIn = $guests->where('checkin_status', CheckinStatus::CheckedIn);

        return $this->success([
            'cards' => [
                'total' => $active,
                'invitations_sent' => $invitedCount,
                'confirmed' => $confirmed->count(),
                'pending' => $pending->count(),
                'declined' => $declined->count(),
                'checked_in' => $checkedIn->count(),
            ],
            'rsvp_distribution' => [
                ['key' => 'confirmed', 'label' => 'Confirmed', 'value' => $confirmed->count()],
                ['key' => 'pending', 'label' => 'Pending', 'value' => $pending->count()],
                ['key' => 'maybe', 'label' => 'Maybe', 'value' => $maybe->count()],
                ['key' => 'declined', 'label' => 'Declined', 'value' => $declined->count()],
            ],
            'categories' => $this->breakdown($guests, fn ($g) => $g->category ?: 'Uncategorised'),
            'meal_preferences' => $this->breakdown(
                $confirmed->filter(fn ($g) => (bool) $g->meal_preference),
                fn ($g) => $g->meal_preference,
            ),
            'daily_trends' => $this->dailyTrends($event),
            'response_rate' => $invitedCount > 0 ? round($respondedCount / $invitedCount * 100) : 0,
            'average_response_hours' => $this->averageResponseHours($guests),
            'attendance_forecast' => (int) round(
                $confirmed->sum(fn ($g) => 1 + $g->plus_ones_allowed) + $maybe->count() * 0.5,
            ),
            'checkin' => [
                'checked_in' => $checkedIn->count(),
                'expected' => $confirmed->count(),
                'waiting' => max($confirmed->count() - $checkedIn->count(), 0),
                'vip_arrivals' => $checkedIn->filter(fn ($g) => str_contains(strtolower((string) $g->category), 'vip'))->count(),
                'no_shows' => $guests->where('checkin_status', CheckinStatus::NoShow)->count(),
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Guest>  $guests
     * @return array<int, array{label:string, value:int}>
     */
    private function breakdown($guests, callable $key): array
    {
        return $guests->groupBy($key)
            ->map(fn ($group, $label) => ['label' => (string) $label, 'value' => $group->count()])
            ->sortByDesc('value')
            ->values()
            ->all();
    }

    /**
     * RSVP responses per day for the last 30 days.
     *
     * @return array<int, array{date:string, value:int}>
     */
    private function dailyTrends(Event $event): array
    {
        $since = now()->subDays(29)->startOfDay();
        $counts = $event->rsvpResponses()
            ->where('responded_at', '>=', $since)
            ->get()
            ->groupBy(fn ($r) => $r->responded_at->toDateString())
            ->map->count();

        $out = [];
        for ($d = 0; $d < 30; $d++) {
            $date = $since->copy()->addDays($d)->toDateString();
            $out[] = ['date' => $date, 'value' => (int) ($counts[$date] ?? 0)];
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Guest>  $guests
     */
    private function averageResponseHours($guests): ?float
    {
        $responded = $guests->filter(fn ($g) => $g->rsvp_responded_at !== null);

        if ($responded->isEmpty()) {
            return null;
        }

        $hours = $responded->map(fn ($g) => $g->created_at->diffInHours($g->rsvp_responded_at));

        return round($hours->avg(), 1);
    }
}
