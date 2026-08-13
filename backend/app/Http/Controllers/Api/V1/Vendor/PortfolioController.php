<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorPortfolioResource;
use App\Models\VendorPortfolio;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'portfolios' => VendorPortfolioResource::collection($request->user()->vendorPortfolios()->get()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $item = $request->user()->vendorPortfolios()->create($this->validated($request));

        return $this->created([
            'portfolio' => new VendorPortfolioResource($item),
        ], 'Portfolio item added.');
    }

    public function update(Request $request, VendorPortfolio $portfolio): JsonResponse
    {
        $this->authorizeOwner($request, $portfolio);
        $portfolio->update($this->validated($request));

        return $this->success([
            'portfolio' => new VendorPortfolioResource($portfolio),
        ], 'Portfolio item updated.');
    }

    public function destroy(Request $request, VendorPortfolio $portfolio): JsonResponse
    {
        $this->authorizeOwner($request, $portfolio);
        $portfolio->delete();

        return $this->success(null, 'Portfolio item removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date'],
            'cover_url' => ['nullable', 'string', 'max:500'],
            'media' => ['nullable', 'array'],
            'client_feedback' => ['nullable', 'string', 'max:2000'],
            'is_case_study' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function authorizeOwner(Request $request, VendorPortfolio $portfolio): void
    {
        abort_unless($portfolio->vendor_id === $request->user()->id, 404);
    }
}
