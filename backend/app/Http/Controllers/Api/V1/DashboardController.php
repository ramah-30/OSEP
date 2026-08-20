<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\EventResource;
use App\Models\ActivityLog;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One endpoint, three shapes. Every figure is computed from real (seeded)
 * relational data, so the numbers become live the moment Phase 3 starts writing
 * events, bookings and payments.
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $payload = match ($user->account_type) {
            AccountType::EventPlanner => $this->planner($user),
            AccountType::Client => $this->client($user),
            AccountType::Vendor => $this->vendor($user),
        };

        return $this->success($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function planner(User $user): array
    {
        $events = $user->plannedEvents()->get();
        $eventIds = $events->pluck('id');

        $activeStatuses = [EventStatus::Planning, EventStatus::ClientApproval, EventStatus::Execution];
        $active = $events->whereIn('status', $activeStatuses)->count();
        $completed = $events->where('status', EventStatus::Completed)->count();

        $revenue = (float) $events->sum('budget_total');

        return [
            'greeting' => $user->first_name,
            'stats' => [
                ['key' => 'active_events', 'label' => 'Active Events', 'value' => $active, 'icon' => 'CalendarClock', 'accent' => 'navy'],
                ['key' => 'completed_events', 'label' => 'Completed Events', 'value' => $completed, 'icon' => 'CheckCircle2', 'accent' => 'emerald'],
                ['key' => 'revenue', 'label' => 'Total Revenue', 'value' => $revenue, 'format' => 'currency', 'icon' => 'Wallet', 'accent' => 'purple'],
            ],
            'recent_events' => EventResource::collection(
                $user->plannedEvents()->with('client')->latest()->take(5)->get()
            ),
            'recent_activities' => ActivityResource::collection(
                ActivityLog::whereIn('event_id', $eventIds)->with('user')->latest()->take(6)->get()
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function client(User $user): array
    {
        $events = $user->clientEvents()->with(['planner.plannerProfile', 'milestones', 'clientUpdates.user'])->get();
        $event = $events->first();

        $upcoming = $events->where('status', '!=', EventStatus::Completed)->count();
        $remaining = (float) $events->sum(fn ($e) => $e->budgetRemaining());

        return [
            'greeting' => $user->first_name,
            'event' => $event ? new EventResource($event) : null,
            'stats' => [
                ['key' => 'upcoming_events', 'label' => 'Upcoming Events', 'value' => $upcoming, 'icon' => 'CalendarClock', 'accent' => 'navy'],
                ['key' => 'progress', 'label' => 'Planning Progress', 'value' => $event?->progress ?? 0, 'format' => 'percent', 'icon' => 'TrendingUp', 'accent' => 'emerald'],
                ['key' => 'total_events', 'label' => 'My Events', 'value' => $events->count(), 'icon' => 'CalendarCheck2', 'accent' => 'purple'],
                ['key' => 'payment_status', 'label' => 'Balance Remaining', 'value' => $remaining, 'format' => 'currency', 'icon' => 'Wallet', 'accent' => 'navy'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vendor(User $user): array
    {
        $user->loadMissing('vendorProfile');
        $profile = $user->vendorProfile;

        return [
            'greeting' => $user->first_name,
            'business' => [
                'business_name' => $profile?->business_name,
                'category' => $profile?->category,
                'verification_status' => $profile?->verification_status?->value,
                'verification_status_label' => $profile?->verification_status?->label(),
                'availability_status' => $profile?->availability_status?->value,
                'availability_status_label' => $profile?->availability_status?->label(),
                'logo_url' => $profile?->logo_url,
            ],
            'stats' => [
                ['key' => 'profile_views', 'label' => 'Profile Views', 'value' => $profile?->profile_views ?? 0, 'icon' => 'Eye', 'accent' => 'navy'],
                ['key' => 'booking_requests', 'label' => 'Booking Requests', 'value' => $profile?->pending_requests ?? 0, 'icon' => 'ClipboardList', 'accent' => 'purple'],
                ['key' => 'completed_jobs', 'label' => 'Completed Jobs', 'value' => $profile?->completed_jobs ?? 0, 'icon' => 'CheckCircle2', 'accent' => 'emerald'],
                ['key' => 'reviews', 'label' => 'Reviews', 'value' => $profile?->reviews_count ?? 0, 'meta' => $profile?->rating !== null ? (float) $profile->rating : null, 'icon' => 'Star', 'accent' => 'navy'],
            ],
        ];
    }
}
