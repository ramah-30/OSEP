<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorCategoryResource;
use App\Models\VendorCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin CRUD over the vendor category taxonomy. Admins can add custom categories
 * without a code change (the spec requirement).
 */
class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = VendorCategory::query()
            ->withCount('vendors')
            ->orderBy('sort_order')->orderBy('name')->get();

        return $this->success([
            'categories' => VendorCategoryResource::collection($categories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:vendor_categories,name'],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category = VendorCategory::create([
            ...$data,
            'is_custom' => true,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return $this->created([
            'category' => new VendorCategoryResource($category),
        ], 'Category created.');
    }

    public function update(Request $request, VendorCategory $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:vendor_categories,name,'.$category->id],
            'icon' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update($data);

        return $this->success([
            'category' => new VendorCategoryResource($category->loadCount('vendors')),
        ], 'Category updated.');
    }

    public function destroy(VendorCategory $category): JsonResponse
    {
        abort_if($category->vendors()->exists(), 422, 'Reassign vendors before deleting this category.');
        $category->delete();

        return $this->success(null, 'Category deleted.');
    }
}
