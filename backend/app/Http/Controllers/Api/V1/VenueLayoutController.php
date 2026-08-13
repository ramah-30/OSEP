<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVenueLayoutRequest;
use App\Http\Requests\UpdateSeatingRequest;
use App\Http\Requests\UpdateVenueLayoutRequest;
use App\Http\Resources\VenueLayoutResource;
use App\Http\Resources\VenueObjectResource;
use App\Models\Event;
use App\Models\VenueLayout;
use App\Models\VenueObject;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The Venue Designer's persistence: floor-plan layouts, their objects (bulk
 * synced from the canvas on autosave) and per-table seating assignments.
 */
class VenueLayoutController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $layouts = $event->venueLayouts()->withCount('objects')->get();

        return $this->success([
            'layouts' => VenueLayoutResource::collection($layouts),
        ]);
    }

    public function store(StoreVenueLayoutRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $layout = $event->venueLayouts()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'version' => (int) $event->venueLayouts()->max('version') + 1,
        ]);

        return $this->created([
            'layout' => new VenueLayoutResource($layout->loadCount('objects')),
        ], 'Layout created.');
    }

    public function show(Request $request, Event $event, VenueLayout $layout): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $layout);

        $layout->load(['objects.seating.guest']);

        return $this->success(['layout' => new VenueLayoutResource($layout)]);
    }

    public function update(UpdateVenueLayoutRequest $request, Event $event, VenueLayout $layout): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $layout);

        $data = $request->validated();
        $objects = $data['objects'] ?? null;
        unset($data['objects']);

        DB::transaction(function () use ($layout, $data, $objects) {
            $layout->fill($data)->save();

            if ($objects !== null) {
                $uids = array_column($objects, 'uid');
                // Remove objects the canvas no longer has (cascades seating).
                $layout->objects()->whereNotIn('uid', $uids ?: ['__none__'])->delete();

                foreach ($objects as $o) {
                    $layout->objects()->updateOrCreate(
                        ['uid' => $o['uid']],
                        [
                            'object_type' => $o['object_type'],
                            'object_name' => $o['object_name'] ?? null,
                            'x_position' => $o['x'],
                            'y_position' => $o['y'],
                            'width' => $o['width'],
                            'height' => $o['height'],
                            'rotation' => $o['rotation'] ?? 0,
                            'color' => $o['color'] ?? null,
                            'layer' => $o['layer'] ?? 'furniture',
                            'properties' => $o['properties'] ?? null,
                        ],
                    );
                }
            }
        });

        $layout->load(['objects.seating.guest']);

        return $this->success([
            'layout' => new VenueLayoutResource($layout),
        ], 'Layout saved.');
    }

    public function destroy(Request $request, Event $event, VenueLayout $layout): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $layout);

        $layout->delete();

        return $this->success(null, 'Layout deleted.');
    }

    public function duplicate(Request $request, Event $event, VenueLayout $layout): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $layout);

        $layout->load('objects.seating');

        $copy = DB::transaction(function () use ($event, $layout, $request) {
            $clone = $event->venueLayouts()->create([
                'created_by' => $request->user()->id,
                'layout_name' => $layout->layout_name.' (copy)',
                'venue_name' => $layout->venue_name,
                'venue_type' => $layout->venue_type,
                'setting' => $layout->setting,
                'width' => $layout->width,
                'height' => $layout->height,
                'unit' => $layout->unit,
                'max_capacity' => $layout->max_capacity,
                'entry_points' => $layout->entry_points,
                'exit_points' => $layout->exit_points,
                'version' => (int) $event->venueLayouts()->max('version') + 1,
                'meta' => $layout->meta,
            ]);

            foreach ($layout->objects as $object) {
                $newObject = $clone->objects()->create([
                    'uid' => $object->uid,
                    'object_type' => $object->object_type,
                    'object_name' => $object->object_name,
                    'x_position' => $object->x_position,
                    'y_position' => $object->y_position,
                    'width' => $object->width,
                    'height' => $object->height,
                    'rotation' => $object->rotation,
                    'color' => $object->color,
                    'layer' => $object->layer,
                    'properties' => $object->properties,
                ]);

                foreach ($object->seating as $seat) {
                    $newObject->seating()->create([
                        'guest_id' => $seat->guest_id,
                        'seat_number' => $seat->seat_number,
                        'notes' => $seat->notes,
                    ]);
                }
            }

            return $clone;
        });

        return $this->created([
            'layout' => new VenueLayoutResource($copy->loadCount('objects')),
        ], 'Layout duplicated.');
    }

    public function updateSeating(UpdateSeatingRequest $request, Event $event, VenueLayout $layout, VenueObject $object): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $layout);
        abort_unless($object->layout_id === $layout->id, 404);

        // Only guests belonging to this event may be seated.
        $eventGuestIds = $event->guests()->pluck('id')->all();
        $seats = $request->validated()['seats'];

        DB::transaction(function () use ($object, $seats, $eventGuestIds) {
            $object->seating()->delete();

            foreach ($seats as $seat) {
                $guestId = ! empty($seat['guest_id']) && in_array($seat['guest_id'], $eventGuestIds, true)
                    ? $seat['guest_id']
                    : null;

                $object->seating()->create([
                    'seat_number' => $seat['seat_number'],
                    'guest_id' => $guestId,
                    'notes' => $seat['notes'] ?? null,
                ]);
            }
        });

        return $this->success([
            'object' => new VenueObjectResource($object->load('seating.guest')),
        ], 'Seating updated.');
    }
}
