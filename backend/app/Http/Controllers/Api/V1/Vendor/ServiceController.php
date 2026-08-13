<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorServiceResource;
use App\Models\VendorService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'services' => VendorServiceResource::collection(
                $request->user()->vendorServices()->with('category')->get()
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $service = $request->user()->vendorServices()->create($this->validated($request));

        return $this->created([
            'service' => new VendorServiceResource($service),
        ], 'Service added.');
    }

    public function update(Request $request, VendorService $service): JsonResponse
    {
        $this->authorizeOwner($request, $service);
        $service->update($this->validated($request));

        return $this->success([
            'service' => new VendorServiceResource($service),
        ], 'Service updated.');
    }

    public function destroy(Request $request, VendorService $service): JsonResponse
    {
        $this->authorizeOwner($request, $service);
        $service->delete();

        return $this->success(null, 'Service removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function authorizeOwner(Request $request, VendorService $service): void
    {
        abort_unless($service->vendor_id === $request->user()->id, 404);
    }
}
