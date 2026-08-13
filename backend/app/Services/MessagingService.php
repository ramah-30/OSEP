<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Conversation;
use App\Models\PlannerBookingRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The rules for who may message whom, plus the helpers to open a conversation
 * and list a user's allowed contacts. The planner is the hub: clients talk to
 * planners, vendors talk to planners, but clients and vendors never talk
 * directly to each other.
 */
class MessagingService
{
    /**
     * Unordered pairs of account types that are allowed to message each other.
     *
     * @var array<int, array{0: AccountType, 1: AccountType}>
     */
    private const ALLOWED_PAIRS = [
        [AccountType::Client, AccountType::EventPlanner],
        [AccountType::EventPlanner, AccountType::Vendor],
    ];

    public function canMessage(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        $types = [$a->account_type, $b->account_type];

        foreach (self::ALLOWED_PAIRS as $pair) {
            if (in_array($pair[0], $types, true) && in_array($pair[1], $types, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Open (or create) the single conversation between two users, enforcing the
     * permission matrix. Returns null when the pair is not allowed to talk.
     */
    public function openConversation(User $me, User $other): ?Conversation
    {
        if (! $this->canMessage($me, $other)) {
            return null;
        }

        return Conversation::between($me->id, $other->id);
    }

    /**
     * The users the given user is allowed to start a conversation with, drawn
     * from the working relationships they already have.
     *
     * @return Collection<int, User>
     */
    public function contactsFor(User $user): Collection
    {
        $contacts = match ($user->account_type) {
            AccountType::Client => $this->plannersForClient($user),
            AccountType::EventPlanner => $this->clientsForPlanner($user)->merge($this->vendorsForPlanner($user)),
            AccountType::Vendor => $this->plannersForVendor($user),
            default => collect(),
        };

        return $contacts->unique('id')->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function plannersForClient(User $client): Collection
    {
        $fromEvents = $client->clientEvents()->pluck('planner_id');
        $fromRequests = PlannerBookingRequest::where('client_id', $client->id)->pluck('planner_id');

        return User::whereIn('id', $fromEvents->merge($fromRequests)->filter()->unique())
            ->where('account_type', AccountType::EventPlanner->value)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function clientsForPlanner(User $planner): Collection
    {
        $fromEvents = $planner->plannedEvents()->whereNotNull('client_id')->pluck('client_id');
        $fromRequests = PlannerBookingRequest::where('planner_id', $planner->id)->pluck('client_id');

        return User::whereIn('id', $fromEvents->merge($fromRequests)->filter()->unique())
            ->where('account_type', AccountType::Client->value)
            ->get();
    }

    /**
     * Vendors this planner has an existing marketplace booking with.
     *
     * @return Collection<int, User>
     */
    private function vendorsForPlanner(User $planner): Collection
    {
        $vendorIds = \DB::table('booking_requests')
            ->where('planner_id', $planner->id)
            ->whereNotNull('vendor_id')
            ->pluck('vendor_id')
            ->unique();

        return User::whereIn('id', $vendorIds)
            ->where('account_type', AccountType::Vendor->value)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function plannersForVendor(User $vendor): Collection
    {
        $plannerIds = \DB::table('booking_requests')
            ->where('vendor_id', $vendor->id)
            ->pluck('planner_id')
            ->unique();

        return User::whereIn('id', $plannerIds)
            ->where('account_type', AccountType::EventPlanner->value)
            ->get();
    }
}
