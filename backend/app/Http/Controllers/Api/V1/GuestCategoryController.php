<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestCategoryRequest;
use App\Http\Requests\UpdateGuestCategoryRequest;
use App\Http\Resources\GuestCategoryResource;
use App\Models\GuestCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Rich guest categories (colour / priority / default seating) for the current
 * planner. Global defaults (null owner) are visible to everyone and read-only;
 * planners fully manage their own.
 */
class GuestCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $categories = GuestCategory::query()
            ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', $userId))
            ->orderBy('priority')
            ->orderBy('name')
            ->get();

        return $this->success(['categories' => GuestCategoryResource::collection($categories)]);
    }

    public function store(StoreGuestCategoryRequest $request): JsonResponse
    {
        $category = GuestCategory::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return $this->created(['category' => new GuestCategoryResource($category)], 'Category created.');
    }

    public function update(UpdateGuestCategoryRequest $request, GuestCategory $category): JsonResponse
    {
        $this->ensureOwned($request, $category);

        $category->fill($request->validated())->save();

        return $this->success(['category' => new GuestCategoryResource($category)], 'Category updated.');
    }

    public function destroy(Request $request, GuestCategory $category): JsonResponse
    {
        $this->ensureOwned($request, $category);

        $category->delete();

        return $this->success(null, 'Category deleted.');
    }

    private function ensureOwned(Request $request, GuestCategory $category): void
    {
        // Global defaults have no owner; only the creating planner can mutate theirs.
        abort_unless($category->created_by === $request->user()->id, 403, 'This category cannot be modified.');
    }
}
