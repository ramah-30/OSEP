<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorPackageResource;
use App\Models\VendorPackage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'packages' => VendorPackageResource::collection($request->user()->vendorPackages()->get()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $package = $request->user()->vendorPackages()->create($this->validated($request));

        return $this->created([
            'package' => new VendorPackageResource($package),
        ], 'Package created.');
    }

    public function update(Request $request, VendorPackage $package): JsonResponse
    {
        $this->authorizeOwner($request, $package);
        $package->update($this->validated($request));

        return $this->success([
            'package' => new VendorPackageResource($package),
        ], 'Package updated.');
    }

    public function destroy(Request $request, VendorPackage $package): JsonResponse
    {
        $this->authorizeOwner($request, $package);
        $package->delete();

        return $this->success(null, 'Package removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'price_unit' => ['nullable', 'string', 'max:50'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string', 'max:200'],
            'addons' => ['nullable', 'array'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function authorizeOwner(Request $request, VendorPackage $package): void
    {
        abort_unless($package->vendor_id === $request->user()->id, 404);
    }
}
