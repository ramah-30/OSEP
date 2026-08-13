<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use App\Http\Resources\MarketplaceVenueResource;
use App\Models\MarketplaceVenue;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin moderation of venue listings.
 */
class VenueController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function index(Request $request): JsonResponse
    {
        $query = MarketplaceVenue::query()->with('owner');

        if ($search = $request->query('q')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->query('verification')) {
            $query->where('verification_level', $request->query('verification'));
        }
        if ($request->filled('suspended')) {
            $query->where('is_suspended', $request->boolean('suspended'));
        }

        $paginator = $query->orderByDesc('is_featured')->latest()
            ->paginate((int) $request->integer('per_page', 20))->withQueryString();

        return $this->success([
            'venues' => MarketplaceVenueResource::collection($paginator->getCollection()),
            'meta' => $this->pageMeta($paginator),
        ]);
    }

    public function verify(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $data = $request->validate([
            'level' => ['required', Rule::in(['unverified', 'email_verified', 'business_verified', 'premium_partner'])],
        ]);
        $venue->update(['verification_level' => $data['level']]);

        Notification::create([
            'user_id' => $venue->owner_id,
            'type' => 'verification_updated',
            'title' => 'Venue verification updated',
            'message' => "\"{$venue->name}\" is now: ".$venue->verification_level->label().'.',
            'data' => ['venue_id' => $venue->id],
        ]);

        return $this->success(['venue' => new MarketplaceVenueResource($venue)], 'Verification updated.');
    }

    public function suspend(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $data = $request->validate(['suspended' => ['required', 'boolean']]);
        $venue->update(['is_suspended' => $data['suspended']]);

        return $this->success(
            ['venue' => new MarketplaceVenueResource($venue)],
            $data['suspended'] ? 'Venue suspended.' : 'Venue reinstated.',
        );
    }

    public function feature(Request $request, MarketplaceVenue $venue): JsonResponse
    {
        $data = $request->validate(['featured' => ['required', 'boolean']]);
        $venue->update(['is_featured' => $data['featured']]);

        return $this->success(
            ['venue' => new MarketplaceVenueResource($venue)],
            $data['featured'] ? 'Venue featured.' : 'Venue unfeatured.',
        );
    }
}
