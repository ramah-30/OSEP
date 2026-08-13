<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountType;
use App\Http\Controllers\Api\V1\Marketplace\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Admin moderation of vendor listings: verification tier, suspension, and the
 * featured flag that floats a vendor to the top of the directory.
 */
class VendorController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('users.*')
            ->join('vendor_profiles as vp', 'vp.user_id', '=', 'users.id')
            ->where('users.account_type', AccountType::Vendor->value)
            ->with('vendorProfile.marketplaceCategory');

        if ($search = $request->query('q')) {
            $query->where('vp.business_name', 'like', "%{$search}%");
        }
        if ($request->query('verification')) {
            $query->where('vp.verification_level', $request->query('verification'));
        }
        if ($request->filled('suspended')) {
            $query->where('vp.is_suspended', $request->boolean('suspended'));
        }
        if ($request->query('status')) {
            $query->where('vp.verification_status', $request->query('status'));
        }

        $paginator = $query->orderByDesc('vp.is_featured')->latest('users.created_at')
            ->paginate((int) $request->integer('per_page', 20))->withQueryString();

        return $this->success([
            'vendors' => VendorResource::collection($paginator->getCollection()),
            'meta' => $this->pageMeta($paginator),
        ]);
    }

    public function verify(Request $request, User $vendor): JsonResponse
    {
        $profile = $this->profileFor($vendor);

        $data = $request->validate([
            'level' => ['required', Rule::in(['unverified', 'email_verified', 'business_verified', 'premium_partner'])],
        ]);

        $profile->update([
            'verification_level' => $data['level'],
            'verification_status' => $data['level'] === 'unverified' ? 'pending' : 'verified',
        ]);

        Notification::create([
            'user_id' => $vendor->id,
            'type' => 'verification_updated',
            'title' => 'Verification updated',
            'message' => 'Your marketplace verification is now: '.$profile->verification_level->label().'.',
            'data' => [],
        ]);

        return $this->success(['vendor' => new VendorResource($vendor->fresh('vendorProfile'))], 'Verification updated.');
    }

    public function suspend(Request $request, User $vendor): JsonResponse
    {
        $profile = $this->profileFor($vendor);
        $data = $request->validate(['suspended' => ['required', 'boolean']]);
        $profile->update(['is_suspended' => $data['suspended']]);

        return $this->success(
            ['vendor' => new VendorResource($vendor->fresh('vendorProfile'))],
            $data['suspended'] ? 'Vendor suspended.' : 'Vendor reinstated.',
        );
    }

    public function feature(Request $request, User $vendor): JsonResponse
    {
        $profile = $this->profileFor($vendor);
        $data = $request->validate(['featured' => ['required', 'boolean']]);
        $profile->update(['is_featured' => $data['featured']]);

        return $this->success(
            ['vendor' => new VendorResource($vendor->fresh('vendorProfile'))],
            $data['featured'] ? 'Vendor featured.' : 'Vendor unfeatured.',
        );
    }

    private function profileFor(User $vendor): \App\Models\VendorProfile
    {
        abort_unless($vendor->account_type === AccountType::Vendor, 404);

        return $vendor->vendorProfile()->firstOrCreate([]);
    }
}
