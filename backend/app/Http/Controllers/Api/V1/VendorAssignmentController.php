<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VendorAssignmentStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorAssignmentRequest;
use App\Http\Requests\UpdateVendorAssignmentRequest;
use App\Http\Resources\VendorAssignmentResource;
use App\Models\Event;
use App\Models\Notification;
use App\Models\VendorAssignment;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorAssignmentController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        return $this->success([
            'vendor_assignments' => VendorAssignmentResource::collection(
                $event->vendorAssignments()->with('vendor.vendorProfile')->latest()->get()
            ),
        ]);
    }

    public function store(StoreVendorAssignmentRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $assignment = $event->vendorAssignments()->create([
            ...$request->validated(),
            'status' => $request->validated()['status'] ?? VendorAssignmentStatus::Requested->value,
        ]);

        $this->activity->log($event, $request->user(), 'vendor_assigned', "assigned {$assignment->vendor_name} for {$assignment->service}", $assignment);

        // If a registered platform vendor, let them know they've been requested.
        if ($assignment->vendor_id) {
            Notification::create([
                'user_id' => $assignment->vendor_id,
                'type' => 'vendor_request',
                'title' => 'New booking request',
                'message' => "{$request->user()->full_name} requested you for \"{$event->title}\".",
                'data' => ['event_id' => $event->id, 'assignment_id' => $assignment->id],
            ]);
        }

        return $this->created([
            'vendor_assignment' => new VendorAssignmentResource($assignment->load('vendor.vendorProfile')),
        ], 'Vendor assigned.');
    }

    public function update(UpdateVendorAssignmentRequest $request, Event $event, VendorAssignment $assignment): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $assignment);

        $assignment->fill($request->validated())->save();

        return $this->success([
            'vendor_assignment' => new VendorAssignmentResource($assignment->load('vendor.vendorProfile')),
        ], 'Vendor assignment updated.');
    }

    public function destroy(Request $request, Event $event, VendorAssignment $assignment): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $assignment);

        $assignment->delete();

        return $this->success(null, 'Vendor removed from event.');
    }
}
