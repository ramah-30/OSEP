<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\Marketplace\Concerns\GeneratesReference;
use App\Http\Controllers\Api\V1\Vendor\Concerns\ScopesToProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Models\BookingRequest;
use App\Models\Notification;
use App\Models\Quotation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The vendor side of quotations: draft, edit line items, and send to the
 * planner. Totals are always derived from the line items server-side.
 */
class QuotationController extends Controller
{
    use ApiResponse, GeneratesReference, ScopesToProvider;

    public function index(Request $request): JsonResponse
    {
        $query = Quotation::query()->with(['items', 'planner', 'venue', 'event']);
        $quotations = $this->scopeToProvider($query, $request->user())
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()->get();

        return $this->success([
            'quotations' => QuotationResource::collection($quotations),
        ]);
    }

    public function show(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeOwner($request, $quotation);

        return $this->success([
            'quotation' => new QuotationResource($quotation->load(['items', 'planner', 'venue', 'event'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'booking_request_id' => ['required', 'integer', 'exists:booking_requests,id'],
            'timeline' => ['nullable', 'string', 'max:150'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'expires_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $booking = BookingRequest::findOrFail($data['booking_request_id']);
        abort_unless($this->ownsRecord($request->user(), $booking->vendor_id, $booking->venue_id), 404);

        $quotation = Quotation::create([
            'booking_request_id' => $booking->id,
            'planner_id' => $booking->planner_id,
            'vendor_id' => $booking->vendor_id,
            'venue_id' => $booking->venue_id,
            'event_id' => $booking->event_id,
            'reference' => $this->generateReference('QUO', Quotation::class),
            'currency' => $data['currency'] ?? 'TZS',
            'tax' => $data['tax'] ?? 0,
            'timeline' => $data['timeline'] ?? null,
            'terms' => $data['terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'status' => 'draft',
        ]);

        $this->syncItems($quotation, $data['items']);

        return $this->created([
            'quotation' => new QuotationResource($quotation->fresh('items')),
        ], 'Quotation drafted.');
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeOwner($request, $quotation);
        abort_if(in_array($quotation->status->value, ['accepted', 'rejected'], true), 422, 'This quotation is closed.');

        $data = $request->validate([
            'timeline' => ['nullable', 'string', 'max:150'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'expires_at' => ['nullable', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ]);

        $quotation->fill(array_filter([
            'timeline' => $data['timeline'] ?? null,
            'terms' => $data['terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'currency' => $data['currency'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ], fn ($v) => $v !== null));
        if (array_key_exists('tax', $data)) {
            $quotation->tax = $data['tax'] ?? 0;
        }
        $quotation->save();

        if (! empty($data['items'])) {
            $quotation->items()->delete();
            $this->syncItems($quotation, $data['items']);
        } else {
            $quotation->recalculateTotals();
        }

        return $this->success([
            'quotation' => new QuotationResource($quotation->fresh('items')),
        ], 'Quotation updated.');
    }

    public function send(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeOwner($request, $quotation);
        abort_unless(in_array($quotation->status->value, ['draft', 'negotiating'], true), 422, 'Only a draft can be sent.');

        $quotation->update(['status' => 'sent', 'sent_at' => now()]);

        Notification::create([
            'user_id' => $quotation->planner_id,
            'type' => 'quotation_received',
            'title' => 'Quotation received',
            'message' => $quotation->providerName().' sent you a quotation ('.$quotation->reference.').',
            'data' => ['quotation_id' => $quotation->id],
        ]);

        return $this->success([
            'quotation' => new QuotationResource($quotation->fresh('items')),
        ], 'Quotation sent.');
    }

    public function destroy(Request $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeOwner($request, $quotation);
        abort_unless($quotation->status->value === 'draft', 422, 'Only a draft can be deleted.');
        $quotation->delete();

        return $this->success(null, 'Draft deleted.');
    }

    /**
     * @param  array<int, array{description:string, quantity:float|int, unit_price:float|int}>  $items
     */
    private function syncItems(Quotation $quotation, array $items): void
    {
        foreach (array_values($items) as $i => $item) {
            $quotation->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => round($item['quantity'] * $item['unit_price'], 2),
                'sort_order' => $i,
            ]);
        }

        $quotation->recalculateTotals();
    }

    private function authorizeOwner(Request $request, Quotation $quotation): void
    {
        abort_unless($this->ownsRecord($request->user(), $quotation->vendor_id, $quotation->venue_id), 404);
    }
}
