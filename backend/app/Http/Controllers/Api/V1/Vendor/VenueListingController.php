<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilitySlotResource;
use App\Http\Resources\MarketplaceVenueResource;
use App\Models\MarketplaceVenue;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A vendor managing their own marketplace venue listings, images and calendar.
 */
class VenueListingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'venues' => MarketplaceVenueResource::collection(
                $request->user()->ownedVenues()->with('images')->latest()->get()
            ),
        ]);
    }

    public function show(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $this->authorizeOwner($request, $venue);

        return $this->success([
            'venue' => new MarketplaceVenueResource($venue->load(['images', 'availability'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $venue = $request->user()->ownedVenues()->create($this->validated($request));

        return $this->created([
            'venue' => new MarketplaceVenueResource($venue->load('images')),
        ], 'Venue listing created.');
    }

    public function update(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $this->authorizeOwner($request, $venue);
        $venue->update($this->validated($request));

        return $this->success([
            'venue' => new MarketplaceVenueResource($venue->load('images')),
        ], 'Venue listing updated.');
    }

    public function destroy(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $this->authorizeOwner($request, $venue);
        $venue->delete();

        return $this->success(null, 'Venue listing removed.');
    }

    /** Replace the image gallery in one call. */
    public function syncImages(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $this->authorizeOwner($request, $venue);

        $data = $request->validate([
            'images' => ['present', 'array'],
            'images.*.url' => ['required', 'string', 'max:500'],
            'images.*.caption' => ['nullable', 'string', 'max:200'],
        ]);

        $venue->images()->delete();
        foreach ($data['images'] as $i => $image) {
            $venue->images()->create([
                'url' => $image['url'],
                'caption' => $image['caption'] ?? null,
                'sort_order' => $i,
            ]);
        }

        return $this->success([
            'venue' => new MarketplaceVenueResource($venue->load('images')),
        ], 'Gallery updated.');
    }

    public function upsertAvailability(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $this->authorizeOwner($request, $venue);

        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.date' => ['required', 'date'],
            'slots.*.status' => ['required', Rule::in(['available', 'reserved', 'fully_booked', 'on_leave'])],
            'slots.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['slots'] as $slot) {
            $venue->availability()->updateOrCreate(
                ['date' => $slot['date']],
                ['status' => $slot['status'], 'note' => $slot['note'] ?? null],
            );
        }

        return $this->success([
            'availability' => AvailabilitySlotResource::collection($venue->availability()->orderBy('date')->get()),
        ], 'Availability updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'venue_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'setting' => ['nullable', Rule::in(['indoor', 'outdoor', 'both'])],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'min_capacity' => ['nullable', 'integer', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'layout_options' => ['nullable', 'array'],
            'setup_time' => ['nullable', 'string', 'max:50'],
            'breakdown_time' => ['nullable', 'string', 'max:50'],
            'included_equipment' => ['nullable', 'array'],
            'facilities' => ['nullable', 'array'],
            'accessibility' => ['nullable', 'array'],
            'restrictions' => ['nullable', 'string', 'max:2000'],
            'parking_available' => ['nullable', 'boolean'],
            'parking_capacity' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'price_unit' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'booking_terms' => ['nullable', 'string', 'max:3000'],
            'cover_image_url' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeOwner(Request $request, MarketplaceVenue $venue): void
    {
        abort_unless($venue->owner_id === $request->user()->id, 404);
    }
}
