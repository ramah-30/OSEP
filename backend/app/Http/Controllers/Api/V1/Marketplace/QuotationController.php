<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\GeneratesReference;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Resources\QuotationResource;
use App\Models\Contract;
use App\Models\MarketplaceVenue;
use App\Models\Notification;
use App\Models\Quotation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Planner side of the quotation flow: review quotations vendors have sent and
 * accept / reject / negotiate them. Accepting a quotation generates a draft
 * contract, the hand-off into contract management.
 */
class QuotationController extends Controller
{
    use ApiResponse, GeneratesReference;

    public function index(Request $request): JsonResponse
    {
        $quotations = Quotation::query()
            ->where('planner_id', $request->user()->id)
            ->where('status', '!=', 'draft')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['items', 'vendor.vendorProfile', 'venue', 'event'])
            ->latest()->get();

        return $this->success([
            'quotations' => QuotationResource::collection($quotations),
        ]);
    }

    public function show(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeOwner($request, $quotation);

        return $this->success([
            'quotation' => new QuotationResource($quotation->load(['items', 'vendor.vendorProfile', 'venue', 'event'])),
        ]);
    }

    public function respond(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeOwner($request, $quotation);
        abort_unless($quotation->status->isActionable(), 422, 'This quotation can no longer be actioned.');

        $data = $request->validate([
            'action' => ['required', Rule::in(['accept', 'reject', 'negotiate'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $contract = null;

        match ($data['action']) {
            'accept' => $contract = $this->accept($quotation),
            'reject' => $quotation->update(['status' => 'rejected', 'decided_at' => now()]),
            'negotiate' => $quotation->update(['status' => 'negotiating']),
        };

        $this->notifyVendor($quotation, $data['action'], $data['note'] ?? null);

        return $this->success([
            'quotation' => new QuotationResource($quotation->fresh(['items'])),
            'contract' => $contract ? new ContractResource($contract) : null,
        ], 'Quotation '.$data['action'].'ed.');
    }

    private function accept(Quotation $quotation): Contract
    {
        $quotation->update(['status' => 'accepted', 'decided_at' => now()]);

        return Contract::create([
            'quotation_id' => $quotation->id,
            'booking_request_id' => $quotation->booking_request_id,
            'planner_id' => $quotation->planner_id,
            'vendor_id' => $quotation->vendor_id,
            'venue_id' => $quotation->venue_id,
            'event_id' => $quotation->event_id,
            'reference' => $this->generateReference('CON', Contract::class),
            'title' => 'Contract for '.$quotation->reference,
            'status' => 'draft',
            'amount' => $quotation->total,
            'currency' => $quotation->currency,
            'terms' => $quotation->terms,
        ]);
    }

    private function notifyVendor(Quotation $quotation, string $action, ?string $note): void
    {
        $ownerId = $quotation->vendor_id
            ?? MarketplaceVenue::whereKey($quotation->venue_id)->value('owner_id');

        if (! $ownerId) {
            return;
        }

        $verb = ['accept' => 'accepted', 'reject' => 'rejected', 'negotiate' => 'wants to negotiate'][$action];

        Notification::create([
            'user_id' => $ownerId,
            'type' => 'quotation_'.$action,
            'title' => 'Quotation '.$verb,
            'message' => "A planner {$verb} your quotation {$quotation->reference}.".($note ? " Note: {$note}" : ''),
            'data' => ['quotation_id' => $quotation->id],
        ]);
    }

    private function authorizeOwner(Request $request, Quotation $quotation): void
    {
        abort_unless($quotation->planner_id === $request->user()->id, 404);
    }
}
