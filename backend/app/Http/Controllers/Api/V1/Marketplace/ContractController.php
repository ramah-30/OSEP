<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\MarketplaceVenue;
use App\Models\Notification;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Planner view of contracts. The planner signs a contract the vendor has sent;
 * status beyond that (active/completed) is driven from the vendor side.
 */
class ContractController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $contracts = Contract::query()
            ->where('planner_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['vendor.vendorProfile', 'venue', 'event', 'quotation'])
            ->latest()->get();

        return $this->success([
            'contracts' => ContractResource::collection($contracts),
        ]);
    }

    public function show(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeOwner($request, $contract);

        return $this->success([
            'contract' => new ContractResource($contract->load(['vendor.vendorProfile', 'venue', 'event', 'quotation.items'])),
        ]);
    }

    public function sign(Request $request, Contract $contract, ActivityLogger $activity): JsonResponse
    {
        $this->authorizeOwner($request, $contract);
        abort_unless($contract->status->value === 'sent', 422, 'Only a sent contract can be signed.');

        $contract->update(['status' => 'signed', 'signed_at' => now()]);

        $ownerId = $contract->vendor_id
            ?? MarketplaceVenue::whereKey($contract->venue_id)->value('owner_id');
        if ($ownerId) {
            Notification::create([
                'user_id' => $ownerId,
                'type' => 'contract_signed',
                'title' => 'Contract signed',
                'message' => "A planner signed contract {$contract->reference}.",
                'data' => ['contract_id' => $contract->id],
            ]);
        }

        // Surface the signed contract on the client's event timeline.
        if ($event = $contract->event()->first()) {
            $activity->log(
                $event,
                $request->user(),
                'contract_signed',
                "Contract {$contract->reference} was signed.",
                $contract,
                visibleToClient: true,
            );
        }

        return $this->success([
            'contract' => new ContractResource($contract),
        ], 'Contract signed.');
    }

    private function authorizeOwner(Request $request, Contract $contract): void
    {
        abort_unless($contract->planner_id === $request->user()->id, 404);
    }
}
