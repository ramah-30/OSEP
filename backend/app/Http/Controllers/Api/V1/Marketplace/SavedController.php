<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\SavedCollectionResource;
use App\Http\Resources\SavedItemResource;
use App\Models\SavedCollection;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A planner's shortlists ("Wedding Vendors", "Luxury Suppliers") and the vendors
 * / venues saved inside them.
 */
class SavedController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $collections = $request->user()->savedCollections()
            ->withCount('items')
            ->with([
                'items.vendor.vendorProfile.marketplaceCategory',
                'items.vendor' => fn ($q) => $q->withMin(['vendorPackages as price_from' => fn ($p) => $p->where('is_active', true)], 'price'),
                'items.venue',
            ])
            ->latest()->get();

        return $this->success([
            'collections' => SavedCollectionResource::collection($collections),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $collection = $request->user()->savedCollections()->create($data);

        return $this->created([
            'collection' => new SavedCollectionResource($collection->loadCount('items')),
        ], 'Collection created.');
    }

    public function update(Request $request, SavedCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        $collection->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]));

        return $this->success([
            'collection' => new SavedCollectionResource($collection->loadCount('items')),
        ], 'Collection updated.');
    }

    public function destroy(Request $request, SavedCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);
        $collection->delete();

        return $this->success(null, 'Collection deleted.');
    }

    public function addItem(Request $request, SavedCollection $collection): JsonResponse
    {
        $this->authorizeOwner($request, $collection);

        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['vendor', 'venue'])],
            'provider_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $provider = $this->resolveProvider($data['provider_type'], (int) $data['provider_id']);

        $item = $collection->items()->updateOrCreate(
            ['vendor_id' => $provider['vendor_id'], 'venue_id' => $provider['venue_id']],
            ['note' => $data['note'] ?? null],
        );

        $item->load(['vendor.vendorProfile.marketplaceCategory', 'venue']);

        return $this->created([
            'item' => new SavedItemResource($item),
        ], 'Saved to collection.');
    }

    public function removeItem(Request $request, SavedCollection $collection, int $item): JsonResponse
    {
        $this->authorizeOwner($request, $collection);
        $collection->items()->whereKey($item)->delete();

        return $this->success(null, 'Removed from collection.');
    }

    private function authorizeOwner(Request $request, SavedCollection $collection): void
    {
        abort_unless($collection->planner_id === $request->user()->id, 404);
    }
}
