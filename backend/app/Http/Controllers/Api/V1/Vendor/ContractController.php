<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\Vendor\Concerns\ScopesToProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The vendor side of contract management: fill in a draft, send it to the
 * planner to sign, then move it through active → completed.
 */
class ContractController extends Controller
{
    use ApiResponse, ScopesToProvider;

    /** Legal status moves the vendor is allowed to drive. */
    private const TRANSITIONS = [
        'draft' => ['sent', 'cancelled'],
        'sent' => ['cancelled'],
        'signed' => ['active', 'cancelled'],
        'active' => ['completed', 'cancelled'],
    ];

    public function index(Request $request): JsonResponse
    {
        $query = Contract::query()->with(['planner', 'venue', 'event', 'quotation']);
        $contracts = $this->scopeToProvider($query, $request->user())
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()->get();

        return $this->success([
            'contracts' => ContractResource::collection($contracts),
        ]);
    }

    public function show(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeOwner($request, $contract);

        return $this->success([
            'contract' => new ContractResource($contract->load(['planner', 'venue', 'event', 'quotation.items'])),
        ]);
    }

    public function update(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeOwner($request, $contract);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'document_path' => ['nullable', 'string', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $contract->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return $this->success([
            'contract' => new ContractResource($contract),
        ], 'Contract updated.');
    }

    public function transition(Request $request, Contract $contract): JsonResponse
    {
        $this->authorizeOwner($request, $contract);

        $data = $request->validate([
            'status' => ['required', Rule::in(['sent', 'active', 'completed', 'cancelled'])],
        ]);

        $allowed = self::TRANSITIONS[$contract->status->value] ?? [];
        abort_unless(in_array($data['status'], $allowed, true), 422, 'Illegal contract transition.');

        $contract->update([
            'status' => $data['status'],
            'signed_at' => $contract->signed_at,
        ]);

        Notification::create([
            'user_id' => $contract->planner_id,
            'type' => 'contract_'.$data['status'],
            'title' => 'Contract '.$contract->status->label(),
            'message' => "Contract {$contract->reference} is now {$contract->status->label()}.",
            'data' => ['contract_id' => $contract->id],
        ]);

        return $this->success([
            'contract' => new ContractResource($contract),
        ], 'Contract updated.');
    }

    private function authorizeOwner(Request $request, Contract $contract): void
    {
        abort_unless($this->ownsRecord($request->user(), $contract->vendor_id, $contract->venue_id), 404);
    }
}
