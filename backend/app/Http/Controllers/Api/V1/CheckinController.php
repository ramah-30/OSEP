<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CheckinStatus;
use App\Enums\CommunicationType;
use App\Enums\RsvpStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckinRequest;
use App\Http\Resources\CheckinResource;
use App\Http\Resources\GuestResource;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Notification;
use App\Models\QrCode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $query = $event->guests()->whereNull('archived_at')->with(['checkin', 'qrCode']);

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($status = $request->query('checkin_status')) {
            $query->where('checkin_status', $status);
        }

        return $this->success([
            'guests' => GuestResource::collection($query->orderBy('full_name')->get()),
            'statistics' => $this->stats($event),
        ]);
    }

    public function statistics(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        return $this->success(['statistics' => $this->stats($event)]);
    }

    public function store(CheckinRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();
        $method = 'manual';
        $qrCodeId = null;

        if (! empty($data['token'])) {
            $qr = QrCode::where('token', $data['token'])->first();

            if (! $qr || $qr->event_id !== $event->id || ! $qr->isActive()) {
                return $this->error('This ticket is not valid for this event.', null, 422);
            }
            $guest = $event->guests()->find($qr->guest_id);
            $method = 'qr';
            $qrCodeId = $qr->id;
        } else {
            $guest = $event->guests()->find($data['guest_id']);
        }

        if (! $guest) {
            return $this->error('Guest not found for this event.', null, 404);
        }

        if ($guest->checkin()->exists()) {
            return $this->error("{$guest->full_name} is already checked in.", null, 409);
        }

        $checkin = $guest->checkin()->create([
            'event_id' => $event->id,
            'qr_code_id' => $qrCodeId,
            'checked_in_by' => $request->user()->id,
            'method' => $method,
            'party_size' => $data['party_size'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'checked_in_at' => now(),
        ]);

        $guest->forceFill([
            'checkin_status' => CheckinStatus::CheckedIn->value,
            'checked_in_at' => now(),
        ])->save();

        $guest->communicationLogs()->create([
            'event_id' => $event->id,
            'created_by' => $request->user()->id,
            'type' => CommunicationType::Checkin->value,
            'title' => 'Checked in',
            'detail' => $method === 'qr' ? 'Via QR scan' : 'Manual check-in',
        ]);

        Notification::create([
            'user_id' => $event->planner_id,
            'type' => 'guest_checked_in',
            'title' => 'Guest checked in',
            'message' => "{$guest->full_name} has arrived at {$event->title}.",
            'data' => ['event_id' => $event->id, 'guest_id' => $guest->id],
        ]);

        return $this->created([
            'checkin' => new CheckinResource($checkin),
            'guest' => new GuestResource($guest->load('checkin')),
            'statistics' => $this->stats($event),
        ], "{$guest->full_name} checked in.");
    }

    public function destroy(Request $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $guest->checkin()->delete();
        $guest->forceFill([
            'checkin_status' => CheckinStatus::Pending->value,
            'checked_in_at' => null,
        ])->save();

        return $this->success([
            'guest' => new GuestResource($guest),
            'statistics' => $this->stats($event),
        ], 'Check-in undone.');
    }

    /**
     * @return array<string, int>
     */
    private function stats(Event $event): array
    {
        $guests = $event->guests()->whereNull('archived_at')->get();
        $checkedIn = $guests->where('checkin_status', CheckinStatus::CheckedIn);
        $confirmed = $guests->where('rsvp_status', RsvpStatus::Confirmed);

        return [
            'checked_in' => $checkedIn->count(),
            'expected' => $confirmed->count(),
            'waiting' => max($confirmed->count() - $checkedIn->count(), 0),
            'vip_arrivals' => $checkedIn->filter(fn ($g) => str_contains(strtolower((string) $g->category), 'vip'))->count(),
            'no_shows' => $guests->where('checkin_status', CheckinStatus::NoShow)->count(),
            'total' => $guests->count(),
        ];
    }
}
