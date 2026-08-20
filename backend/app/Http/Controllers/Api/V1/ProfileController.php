<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileImageRequest;
use App\Http\Requests\UpdateClientProfileRequest;
use App\Http\Requests\UpdatePlannerProfileRequest;
use App\Http\Requests\UpdateVendorProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\ClientProfile;
use App\Models\PlannerProfile;
use App\Models\VendorProfile;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    public function show(Request $request): JsonResponse
    {
        return $this->success([
            'profile' => new ProfileResource($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate against the rule set for the caller's account type, then write
        // to the matching profile table.
        $rules = match ($user->account_type) {
            AccountType::EventPlanner => (new UpdatePlannerProfileRequest)->rules(),
            AccountType::Client => (new UpdateClientProfileRequest)->rules(),
            AccountType::Vendor => (new UpdateVendorProfileRequest)->rules(),
        };

        $data = $request->validate($rules);

        $this->auth->ensureProfile($user);
        $profile = $this->profileFor($user);
        $profile->fill($data)->save();

        return $this->success([
            'profile' => new ProfileResource($user->refresh()),
        ], 'Profile updated.');
    }

    public function uploadImage(ProfileImageRequest $request): JsonResponse
    {
        $user = $request->user();

        $folder = $user->account_type === AccountType::Vendor ? 'logos' : 'avatars';
        $path = $request->file('image')->store($folder, 'public');
        $url = Storage::disk('public')->url($path);

        // Swap the old file out so orphaned uploads do not pile up.
        $previous = $user->avatar_url;

        $user->forceFill(['avatar_url' => $url])->save();

        // A vendor's logo mirrors onto the vendor profile so listings can read it
        // without joining back to the user.
        if ($user->account_type === AccountType::Vendor) {
            $this->auth->ensureProfile($user);
            $user->vendorProfile->forceFill(['logo_url' => $url])->save();
        }

        $this->deletePrevious($previous);

        return $this->success([
            'avatar_url' => $url,
            'profile' => new ProfileResource($user->refresh()),
        ], 'Image uploaded.');
    }

    private function profileFor(\App\Models\User $user): PlannerProfile|ClientProfile|VendorProfile
    {
        return match ($user->account_type) {
            AccountType::EventPlanner => $user->plannerProfile()->firstOrCreate([]),
            AccountType::Client => $user->clientProfile()->firstOrCreate([]),
            AccountType::Vendor => $user->vendorProfile()->firstOrCreate([]),
        };
    }

    private function deletePrevious(?string $url): void
    {
        if (! $url) {
            return;
        }

        $prefix = Storage::disk('public')->url('');
        if (str_starts_with($url, $prefix)) {
            Storage::disk('public')->delete(substr($url, strlen($prefix)));
        }
    }
}
