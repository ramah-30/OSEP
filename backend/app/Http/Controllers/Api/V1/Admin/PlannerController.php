<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Http\Controllers\Api\V1\Marketplace\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin moderation of event-planner accounts: list, verify (admin-approved) and
 * suspend, mirroring how vendors are moderated in the marketplace.
 */
class PlannerController extends Controller
{
    use ApiResponse, PaginatesResults;

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('users.*')
            ->leftJoin('planner_profiles as pp', 'pp.user_id', '=', 'users.id')
            ->where('users.account_type', AccountType::EventPlanner->value)
            ->with('plannerProfile')
            ->withCount('plannedEvents as events_count');

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('pp.company_name', 'like', "%{$search}%");
            });
        }

        if ($request->query('verification') === 'verified') {
            $query->whereNotNull('pp.verified_at');
        } elseif ($request->query('verification') === 'pending') {
            $query->whereNull('pp.verified_at');
        }

        if ($request->filled('suspended')) {
            $request->boolean('suspended')
                ? $query->where('users.status', UserStatus::Suspended->value)
                : $query->where('users.status', '!=', UserStatus::Suspended->value);
        }

        $paginator = $query->latest('users.created_at')
            ->paginate((int) $request->integer('per_page', 20))->withQueryString();

        return $this->success([
            'planners' => $paginator->getCollection()->map(fn (User $u) => $this->present($u)),
            'meta' => $this->pageMeta($paginator),
        ]);
    }

    public function verify(Request $request, User $planner): JsonResponse
    {
        $profile = $this->profileFor($planner);
        $data = $request->validate(['verified' => ['required', 'boolean']]);

        $profile->update(['verified_at' => $data['verified'] ? now() : null]);

        Notification::create([
            'user_id' => $planner->id,
            'type' => 'verification_updated',
            'title' => $data['verified'] ? 'Account verified' : 'Verification removed',
            'message' => $data['verified']
                ? 'Your planner account has been verified by an administrator.'
                : 'Your planner account verification has been removed.',
            'data' => [],
        ]);

        return $this->success(['planner' => $this->present($planner->fresh(['plannerProfile']))],
            $data['verified'] ? 'Planner verified.' : 'Verification removed.');
    }

    public function suspend(Request $request, User $planner): JsonResponse
    {
        $this->profileFor($planner);
        $data = $request->validate(['suspended' => ['required', 'boolean']]);

        $planner->update(['status' => $data['suspended'] ? UserStatus::Suspended : UserStatus::Active]);

        if ($data['suspended']) {
            $planner->tokens()->delete();
        }

        return $this->success(['planner' => $this->present($planner->fresh(['plannerProfile']))],
            $data['suspended'] ? 'Planner suspended.' : 'Planner reinstated.');
    }

    private function profileFor(User $planner): \App\Models\PlannerProfile
    {
        abort_unless($planner->account_type === AccountType::EventPlanner, 404);

        return $planner->plannerProfile()->firstOrCreate([]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $u): array
    {
        return [
            'id' => $u->id,
            'full_name' => $u->full_name,
            'email' => $u->email,
            'phone' => $u->phone,
            'avatar_url' => $u->avatar_url,
            'company_name' => $u->plannerProfile?->company_name,
            'specialization' => $u->plannerProfile?->specialization,
            'location' => $u->plannerProfile?->location,
            'events_count' => (int) ($u->events_count ?? 0),
            'status' => $u->status->value,
            'is_suspended' => $u->status === UserStatus::Suspended,
            'is_verified' => $u->plannerProfile?->verified_at !== null,
            'verified_at' => $u->plannerProfile?->verified_at?->toIso8601String(),
        ];
    }
}
