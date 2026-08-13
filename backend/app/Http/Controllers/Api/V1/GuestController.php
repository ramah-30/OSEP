<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CommunicationType;
use App\Enums\InvitationChannel;
use App\Enums\RsvpStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkGuestActionRequest;
use App\Http\Requests\BulkStoreGuestsRequest;
use App\Http\Requests\ImportGuestsRequest;
use App\Http\Requests\StoreCommunicationNoteRequest;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Http\Resources\CommunicationLogResource;
use App\Http\Resources\GuestResource;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestCsvService;
use App\Services\InvitationDispatcher;
use App\Services\QrCodeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $query = $event->guests()->with(['qrCode', 'checkin']);

        // Archived guests are hidden unless explicitly requested.
        $archived = $request->query('archived');
        if ($archived === 'only') {
            $query->whereNotNull('archived_at');
        } elseif (! $request->boolean('archived')) {
            $query->whereNull('archived_at');
        }

        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        foreach (['rsvp_status', 'invitation_status', 'checkin_status', 'category'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        if ($request->query('meal_choice')) {
            $query->where('meal_preference', $request->query('meal_choice'));
        }

        $sort = in_array($request->query('sort'), ['full_name', 'category', 'rsvp_status', 'invitation_status', 'created_at'], true)
            ? $request->query('sort')
            : 'full_name';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $guests = $query->orderBy($sort, $dir)->get();

        return $this->success([
            'guests' => GuestResource::collection($guests),
            'summary' => $this->summary($event),
        ]);
    }

    public function store(StoreGuestRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $guest = $event->guests()->create([
            ...$request->validated(),
            'rsvp_status' => $request->validated()['rsvp_status'] ?? RsvpStatus::Pending->value,
        ]);

        // refresh() so the DB defaults (invitation/check-in status) are hydrated.
        return $this->created(['guest' => new GuestResource($guest->refresh()->loadMissing(['qrCode', 'checkin']))], 'Guest added.');
    }

    public function update(UpdateGuestRequest $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $guest->fill($request->validated())->save();

        return $this->success(['guest' => new GuestResource($guest->load(['qrCode', 'checkin']))], 'Guest updated.');
    }

    public function destroy(Request $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $guest->delete();

        return $this->success(null, 'Guest removed.');
    }

    public function archive(Request $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $guest->forceFill(['archived_at' => $guest->archived_at ? null : now()])->save();

        return $this->success(
            ['guest' => new GuestResource($guest)],
            $guest->archived_at ? 'Guest archived.' : 'Guest restored.',
        );
    }

    public function duplicate(Request $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $copy = $event->guests()->create([
            'first_name' => $guest->first_name,
            'last_name' => $guest->last_name,
            'full_name' => $guest->full_name.' (copy)',
            'email' => null, // avoid a duplicate contact clash
            'phone' => $guest->phone,
            'gender' => $guest->gender,
            'category' => $guest->category,
            'meal_preference' => $guest->meal_preference,
            'dietary_restrictions' => $guest->dietary_restrictions,
            'accessibility_requirements' => $guest->accessibility_requirements,
            'plus_ones_allowed' => $guest->plus_ones_allowed,
            'rsvp_status' => RsvpStatus::Pending->value,
        ]);

        return $this->created(['guest' => new GuestResource($copy->refresh())], 'Guest duplicated.');
    }

    public function bulkStore(BulkStoreGuestsRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $created = 0;
        foreach ($request->validated()['guests'] as $row) {
            $event->guests()->create([...$row, 'rsvp_status' => RsvpStatus::Pending->value]);
            $created++;
        }

        return $this->created(['created' => $created, 'summary' => $this->summary($event)], "{$created} guests added.");
    }

    public function bulkAction(BulkGuestActionRequest $request, Event $event, InvitationDispatcher $dispatcher): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();
        $guests = $event->guests()->whereIn('id', $data['guest_ids'])->get();
        $affected = $guests->count();

        switch ($data['action']) {
            case 'send_invitations':
                $channel = InvitationChannel::tryFrom($data['channel'] ?? '') ?? InvitationChannel::Email;
                foreach ($guests as $guest) {
                    $dispatcher->send($guest, $channel, null, $request->user());
                }
                $message = "Invitations sent to {$affected} guests.";
                break;

            case 'update_category':
                $event->guests()->whereIn('id', $data['guest_ids'])->update(['category' => $data['category']]);
                $message = "Category updated for {$affected} guests.";
                break;

            case 'assign_table':
                $event->guests()->whereIn('id', $data['guest_ids'])->update(['seat_number' => $data['seat_number']]);
                $message = "Table assigned for {$affected} guests.";
                break;

            case 'archive':
                $event->guests()->whereIn('id', $data['guest_ids'])->update(['archived_at' => now()]);
                $message = "{$affected} guests archived.";
                break;

            case 'delete':
                $event->guests()->whereIn('id', $data['guest_ids'])->delete();
                $message = "{$affected} guests removed.";
                break;

            default:
                $message = 'No action taken.';
        }

        return $this->success(['summary' => $this->summary($event->refresh())], $message);
    }

    public function import(ImportGuestsRequest $request, Event $event, GuestCsvService $csv): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $contents = file_get_contents($request->file('file')->getRealPath());
        $report = $csv->import($event, $contents);

        return $this->success([
            'imported' => $report['imported'],
            'duplicates' => $report['duplicates'],
            'errors' => $report['errors'],
            'summary' => $this->summary($event->refresh()),
        ], "{$report['imported']} guests imported.");
    }

    public function export(Request $request, Event $event, GuestCsvService $csv): StreamedResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $guests = $event->guests()->orderBy('full_name')->get();
        $body = $csv->export($guests);
        $filename = 'guests-'.$event->event_code.'.csv';

        return response()->streamDownload(fn () => print ($body), $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function ticket(Request $request, Event $event, Guest $guest, QrCodeService $qr): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        // A ticket only exists once the guest has accepted their invitation.
        abort_unless(
            $guest->rsvp_status->hasTicket(),
            422,
            'A ticket is issued once the guest confirms their RSVP.',
        );

        $qr->ensureFor($guest);

        return $this->success([
            'ticket' => new TicketResource($guest->load(['event', 'qrCode'])),
        ]);
    }

    public function note(StoreCommunicationNoteRequest $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        $log = $guest->communicationLogs()->create([
            'event_id' => $event->id,
            'created_by' => $request->user()->id,
            'type' => CommunicationType::Note->value,
            'title' => $request->validated()['title'],
            'detail' => $request->validated()['detail'] ?? null,
        ]);

        return $this->created(['log' => new CommunicationLogResource($log)], 'Note added.');
    }

    public function history(Request $request, Event $event, Guest $guest): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $guest);

        return $this->success([
            'guest' => new GuestResource($guest->load(['invitations', 'rsvpResponses', 'qrCode', 'checkin'])),
            'logs' => CommunicationLogResource::collection($guest->communicationLogs()->get()),
        ]);
    }

    /**
     * Headline counts shared by the list and dashboard cards.
     *
     * @return array<string, int>
     */
    private function summary(Event $event): array
    {
        $base = $event->guests()->whereNull('archived_at');

        return [
            'total' => (clone $base)->count(),
            'invited' => (clone $base)->whereNotIn('invitation_status', ['draft'])->count(),
            'confirmed' => (clone $base)->where('rsvp_status', RsvpStatus::Confirmed->value)->count(),
            'declined' => (clone $base)->where('rsvp_status', RsvpStatus::Declined->value)->count(),
            'maybe' => (clone $base)->where('rsvp_status', RsvpStatus::Maybe->value)->count(),
            'pending' => (clone $base)->whereIn('rsvp_status', RsvpStatus::pendingStates())->count(),
            'checked_in' => (clone $base)->where('checkin_status', 'checked_in')->count(),
        ];
    }
}
