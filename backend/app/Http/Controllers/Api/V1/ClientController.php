<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The planner's client book: the clients on their events, plus inline creation
 * so a planner never has to leave the event flow to add one.
 */
class ClientController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Clients this planner has worked with (from events) plus everyone on
        // their roster (added standalone), with a live event count.
        $clientIds = $user->plannedEvents()->whereNotNull('client_id')->pluck('client_id')
            ->merge($user->clients()->pluck('users.id'))
            ->unique();

        $clients = User::whereIn('id', $clientIds)
            ->with('clientProfile')
            ->withCount(['clientEvent as events_count' => fn ($q) => $q->where('planner_id', $user->id)])
            ->orderBy('first_name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'location' => $c->clientProfile?->location,
                'avatar_url' => $c->avatar_url,
            ]);

        return $this->success(['clients' => $clients]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        // The email may already belong to someone: the same person can be a
        // client of several planners. Reuse that account rather than failing on
        // the unique constraint - but only if it's actually a client account.
        $existing = User::where('email', $data['email'])->first();

        if ($existing) {
            if ($existing->account_type !== AccountType::Client) {
                return $this->error(
                    'That email is already in use by another account.',
                    ['email' => ['That email is already in use by another account.']],
                    422,
                );
            }

            $request->user()->clients()->syncWithoutDetaching([$existing->id]);

            return $this->created([
                'client' => [
                    'id' => $existing->id,
                    'full_name' => $existing->full_name,
                    'email' => $existing->email,
                    'phone' => $existing->phone,
                    'location' => $existing->clientProfile?->location,
                ],
            ], 'Client added to your list.');
        }

        $client = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make(Str::random(32)),
            'account_type' => AccountType::Client->value,
            'status' => UserStatus::Active->value,
            'account_claimed' => false,
        ]);

        $client->assignRole(AccountType::Client->value);
        $this->auth->ensureProfile($client);

        // Put the new client on this planner's roster so they show up in the
        // list even before they're attached to an event.
        $request->user()->clients()->syncWithoutDetaching([$client->id]);

        if (! empty($data['location'])) {
            $client->clientProfile()->update(['location' => $data['location']]);
        }

        return $this->created([
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'location' => $data['location'] ?? null,
            ],
        ], 'Client created.');
    }

    /**
     * Look up a client account by email so the planner can pre-fill the event
     * form without re-entering details for a returning client.
     */
    public function lookup(Request $request): JsonResponse
    {
        $email = trim((string) $request->query('email', ''));

        if (! $email) {
            return $this->error('email is required', 422);
        }

        $client = User::where('email', $email)
            ->where('account_type', AccountType::Client->value)
            ->with('clientProfile')
            ->first();

        if (! $client) {
            return $this->success(['client' => null]);
        }

        return $this->success([
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'location' => $client->clientProfile?->location,
                'account_claimed' => $client->account_claimed,
            ],
        ]);
    }

    public function update(UpdateClientRequest $request, User $client): JsonResponse
    {
        if (! $this->ownsClient($request->user(), $client)) {
            return $this->error('Not found.', 404);
        }

        $data = $request->validated();

        $client->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        $this->auth->ensureProfile($client);
        $client->clientProfile()->update(['location' => $data['location'] ?? null]);

        return $this->success([
            'client' => [
                'id' => $client->id,
                'full_name' => $client->full_name,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'location' => $data['location'] ?? null,
            ],
        ], 'Client updated.');
    }

    public function destroy(Request $request, User $client): JsonResponse
    {
        if (! $this->ownsClient($request->user(), $client)) {
            return $this->error('Not found.', 404);
        }

        // events.client_id is nullOnDelete, so this planner's events keep their
        // history minus the link; booking requests cascade away with the account.
        $client->delete();

        return $this->success([], 'Client removed.');
    }

    /**
     * A planner may only touch clients that sit on one of their own events.
     */
    private function ownsClient(User $planner, User $client): bool
    {
        return $client->account_type === AccountType::Client
            && ($planner->clients()->where('users.id', $client->id)->exists()
                || $planner->plannedEvents()->where('client_id', $client->id)->exists());
    }
}
