<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full event record for the planner workspace and the client window. Related
 * collections are only serialised when they have been eager-loaded, so the same
 * resource powers both a light list row and a fully hydrated workspace.
 *
 * @mixin \App\Models\Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_code' => $this->event_code,
            'title' => $this->title,
            'event_type' => $this->event_type,
            'event_category' => $this->event_category,
            'event_date' => $this->event_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'venue' => $this->venue,
            'location' => $this->location,
            'expected_guests' => $this->expected_guests,
            'description' => $this->description,
            'theme' => $this->theme,
            'priority' => $this->priority?->value,
            'priority_label' => $this->priority?->label(),
            'internal_notes' => $this->internal_notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'progress' => $this->progress,
            'budget' => [
                'total' => (float) $this->budget_total,
                'spent' => (float) $this->budget_spent,
                'remaining' => (float) $this->budgetRemaining(),
            ],
            'created_at' => $this->created_at?->toIso8601String(),

            // Counts - present when the caller loaded them with withCount().
            'counts' => [
                'tasks' => $this->whenCounted('tasks'),
                'guests' => $this->whenCounted('guests'),
                'vendor_assignments' => $this->whenCounted('vendorAssignments'),
                'documents' => $this->whenCounted('documents'),
                'open_approvals' => $this->whenCounted('approvals'),
            ],

            'planner' => $this->whenLoaded('planner', fn () => [
                'id' => $this->planner->id,
                'full_name' => $this->planner->full_name,
                'company_name' => $this->planner->plannerProfile?->company_name,
                'avatar_url' => $this->planner->avatar_url,
            ]),
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'full_name' => $this->client->full_name,
                'email' => $this->client->email,
                'avatar_url' => $this->client->avatar_url,
            ] : null),

            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'guests' => GuestResource::collection($this->whenLoaded('guests')),
            'venue_detail' => new VenueResource($this->whenLoaded('venueDetail')),
            'vendor_assignments' => VendorAssignmentResource::collection($this->whenLoaded('vendorAssignments')),
            'budget_items' => BudgetItemResource::collection($this->whenLoaded('budgetItems')),
            'approvals' => ApprovalResource::collection($this->whenLoaded('approvals')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'activities' => ActivityResource::collection($this->whenLoaded('activities')),
            'updates' => ActivityResource::collection($this->whenLoaded('clientUpdates')),
        ];
    }
}
