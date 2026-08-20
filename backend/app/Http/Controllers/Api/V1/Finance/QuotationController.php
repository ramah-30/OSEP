<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\ClientQuotationStatus;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientQuotationResource;
use App\Http\Resources\InvoiceResource;
use App\Models\ClientQuotation;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(ClientQuotationStatus::class)],
            'event_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
        ]);

        $quotations = ClientQuotation::where('planner_id', $request->user()->id)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%")))
            ->with(['client', 'event'])
            ->latest()
            ->get();

        return $this->success([
            'quotations' => ClientQuotationResource::collection($quotations),
            'summary' => $this->summary($request),
        ]);
    }

    public function show(Request $request, ClientQuotation $quotation): JsonResponse
    {
        $this->ensureOwned($request, $quotation);

        return $this->success([
            'quotation' => new ClientQuotationResource($quotation->load(['items', 'client', 'event'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateQuotation($request);
        $event = $this->ownedEvent($request, $data['event_id'] ?? null);

        $quotation = DB::transaction(function () use ($data, $request) {
            $quotation = ClientQuotation::create([
                'reference' => $this->nextNumber('QUO', ClientQuotation::class, 'reference'),
                'planner_id' => $request->user()->id,
                'client_id' => $data['client_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'title' => $data['title'] ?? null,
                'currency' => $data['currency'] ?? 'TZS',
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'status' => ClientQuotationStatus::Draft->value,
            ]);

            $this->syncItems($quotation, $data['items'] ?? []);
            $quotation->recalculateTotals();

            return $quotation;
        });

        $this->logFinance($event, $request, 'quotation_created', "created quotation {$quotation->reference}", $quotation);

        return $this->created([
            'quotation' => new ClientQuotationResource($quotation->load(['items', 'client', 'event'])),
        ], 'Quotation created.');
    }

    public function update(Request $request, ClientQuotation $quotation): JsonResponse
    {
        $this->ensureOwned($request, $quotation);
        abort_if($quotation->status->isDecided(), 422, 'A decided quotation can no longer be edited.');

        $data = $this->validateQuotation($request);
        $this->ownedEvent($request, $data['event_id'] ?? null);

        DB::transaction(function () use ($quotation, $data) {
            $quotation->update([
                'client_id' => $data['client_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'title' => $data['title'] ?? null,
                'currency' => $data['currency'] ?? $quotation->currency,
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            if (array_key_exists('items', $data)) {
                $quotation->items()->delete();
                $this->syncItems($quotation, $data['items']);
            }

            $quotation->recalculateTotals();
        });

        return $this->success([
            'quotation' => new ClientQuotationResource($quotation->load(['items', 'client', 'event'])),
        ], 'Quotation updated.');
    }

    public function destroy(Request $request, ClientQuotation $quotation): JsonResponse
    {
        $this->ensureOwned($request, $quotation);
        $quotation->delete();

        return $this->success(null, 'Quotation deleted.');
    }

    /** Mark the quotation sent (draft → sent). */
    public function send(Request $request, ClientQuotation $quotation): JsonResponse
    {
        $this->ensureOwned($request, $quotation);

        $quotation->update([
            'status' => ClientQuotationStatus::Sent->value,
            'sent_at' => now(),
        ]);

        $this->logFinance($quotation->event, $request, 'quotation_sent', "Quotation {$quotation->reference} was sent for approval.", $quotation, visibleToClient: true);

        return $this->success([
            'quotation' => new ClientQuotationResource($quotation->load(['items', 'client', 'event'])),
        ], 'Quotation sent.');
    }

    /** Planner records the client's decision (accepted / rejected / expired). */
    public function decide(Request $request, ClientQuotation $quotation): JsonResponse
    {
        $this->ensureOwned($request, $quotation);

        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'rejected', 'expired'])],
        ]);

        $quotation->update([
            'status' => $data['status'],
            'decided_at' => now(),
        ]);

        $this->logFinance($quotation->event, $request, 'quotation_'.$data['status'], "quotation {$quotation->reference} {$data['status']}", $quotation);

        return $this->success([
            'quotation' => new ClientQuotationResource($quotation->load(['items', 'client', 'event'])),
        ], 'Quotation updated.');
    }

    /** Turn an accepted quotation into a draft invoice, copying the line items. */
    public function convertToInvoice(Request $request, ClientQuotation $quotation): JsonResponse
    {
        $this->ensureOwned($request, $quotation);
        abort_unless($quotation->status === ClientQuotationStatus::Accepted, 422, 'Only an accepted quotation can be converted.');

        $invoice = DB::transaction(function () use ($quotation, $request) {
            $invoice = Invoice::create([
                'invoice_number' => $this->nextNumber('INV', Invoice::class, 'invoice_number'),
                'planner_id' => $request->user()->id,
                'client_id' => $quotation->client_id,
                'event_id' => $quotation->event_id,
                'client_quotation_id' => $quotation->id,
                'title' => $quotation->title,
                'currency' => $quotation->currency,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'notes' => $quotation->notes,
                'payment_terms' => 'Due within 14 days',
                'status' => InvoiceStatus::Draft->value,
            ]);

            foreach ($quotation->items as $item) {
                $invoice->items()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax' => $item->tax,
                    'discount' => $item->discount,
                    'amount' => $item->amount,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        $this->logFinance($quotation->event, $request, 'invoice_created', "created invoice {$invoice->invoice_number} from quotation {$quotation->reference}", $invoice);

        return $this->created([
            'invoice' => new InvoiceResource($invoice->load(['items', 'client', 'event'])),
        ], 'Invoice created from quotation.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(ClientQuotation $quotation, array $items): void
    {
        foreach (array_values($items) as $index => $row) {
            $amount = (float) $row['quantity'] * (float) $row['unit_price'];
            $quotation->items()->create([
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'tax' => $row['tax'] ?? 0,
                'discount' => $row['discount'] ?? 0,
                'amount' => $amount,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuotation(Request $request): array
    {
        return $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:150'],
            'currency' => ['nullable', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'items' => ['sometimes', 'array'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /**
     * @return array<string, float|int>
     */
    private function summary(Request $request): array
    {
        $base = ClientQuotation::where('planner_id', $request->user()->id);

        return [
            'total_value' => (float) (clone $base)->sum('total'),
            'accepted_value' => (float) (clone $base)->where('status', ClientQuotationStatus::Accepted->value)->sum('total'),
            'pending' => (clone $base)->whereIn('status', [ClientQuotationStatus::Sent->value, ClientQuotationStatus::Viewed->value])->count(),
            'count' => (clone $base)->count(),
        ];
    }
}
