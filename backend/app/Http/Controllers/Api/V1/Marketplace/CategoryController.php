<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorCategoryResource;
use App\Models\VendorCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Read-only category list for the marketplace filters and the Categories page.
 * Counts only non-suspended vendors so the numbers match the directory.
 */
class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = VendorCategory::query()
            ->where('is_active', true)
            ->withCount(['vendors' => fn ($q) => $q->where('is_suspended', false)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'categories' => VendorCategoryResource::collection($categories),
        ]);
    }
}
