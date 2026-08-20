<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(InvoiceStatus::class)],
            'event_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string'],
        ]);

        $this->refreshOverdue($request);

        $invoices = Invoice::where('planner_id', $request->user()->id)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('invoice_number', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%")))
            ->with(['client', 'event'])
            ->latest()
            ->get();

        return $this->success([
            'invoices' => InvoiceResource::collection($invoices),
            'summary' => $this->summary($request),
        ]);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureOwned($request, $invoice);

        return $this->success([
            'invoice' => new InvoiceResource($invoice->load(['items', 'client', 'event', 'payments'])),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateInvoice($request);
        $event = $this->ownedEvent($request, $data['event_id'] ?? null);

        $invoice = DB::transaction(function () use ($data, $request) {
            $invoice = Invoice::create([
                'invoice_number' => $this->nextNumber('INV', Invoice::class, 'invoice_number'),
                'planner_id' => $request->user()->id,
                'client_id' => $data['client_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'title' => $data['title'] ?? null,
                'currency' => $data['currency'] ?? 'TZS',
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => InvoiceStatus::Draft->value,
            ]);

            $this->syncItems($invoice, $data['items'] ?? []);
            $invoice->recalculateTotals();

            return $invoice;
        });

        $this->logFinance($event, $request, 'invoice_created', "created invoice {$invoice->invoice_number}", $invoice);

        return $this->created([
            'invoice' => new InvoiceResource($invoice->load(['items', 'client', 'event'])),
        ], 'Invoice created.');
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureOwned($request, $invoice);
        abort_if(in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true), 422, 'This invoice can no longer be edited.');

        $data = $this->validateInvoice($request);
        $this->ownedEvent($request, $data['event_id'] ?? null);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'client_id' => $data['client_id'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'title' => $data['title'] ?? null,
                'currency' => $data['currency'] ?? $invoice->currency,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['due_date'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (array_key_exists('items', $data)) {
                $invoice->items()->delete();
                $this->syncItems($invoice, $data['items']);
            }

            $invoice->recalculateTotals();
            $invoice->recalculatePaid();
        });

        return $this->success([
            'invoice' => new InvoiceResource($invoice->load(['items', 'client', 'event', 'payments'])),
        ], 'Invoice updated.');
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureOwned($request, $invoice);
        $invoice->delete();

        return $this->success(null, 'Invoice deleted.');
    }

    public function send(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureOwned($request, $invoice);

        if ($invoice->status === InvoiceStatus::Draft) {
            $invoice->update(['status' => InvoiceStatus::Sent->value, 'sent_at' => now()]);
        } elseif (! $invoice->sent_at) {
            $invoice->update(['sent_at' => now()]);
        }

        $this->logFinance($invoice->event, $request, 'invoice_sent', "sent invoice {$invoice->invoice_number}", $invoice);

        return $this->success([
            'invoice' => new InvoiceResource($invoice->load(['items', 'client', 'event', 'payments'])),
        ], 'Invoice sent.');
    }

    public function cancel(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureOwned($request, $invoice);
        abort_if($invoice->status === InvoiceStatus::Paid, 422, 'A paid invoice cannot be cancelled.');

        $invoice->update(['status' => InvoiceStatus::Cancelled->value]);
        $this->logFinance($invoice->event, $request, 'invoice_cancelled', "cancelled invoice {$invoice->invoice_number}", $invoice);

        return $this->success([
            'invoice' => new InvoiceResource($invoice->load(['items', 'client', 'event', 'payments'])),
        ], 'Invoice cancelled.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $index => $row) {
            $invoice->items()->create([
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'tax' => $row['tax'] ?? 0,
                'discount' => $row['discount'] ?? 0,
                'amount' => (float) $row['quantity'] * (float) $row['unit_price'],
                'sort_order' => $index,
            ]);
        }
    }

    /** Flip sent invoices whose due date has passed to overdue. */
    private function refreshOverdue(Request $request): void
    {
        Invoice::where('planner_id', $request->user()->id)
            ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::PartiallyPaid->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->update(['status' => InvoiceStatus::Overdue->value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateInvoice(Request $request): array
    {
        return $request->validate([
            'client_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_id' => ['nullable', 'integer'],
            'title' => ['nullable', 'string', 'max:150'],
            'currency' => ['nullable', 'string', 'size:3'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
        $base = Invoice::where('planner_id', $request->user()->id)->whereNot('status', InvoiceStatus::Cancelled->value);

        return [
            'total_billed' => (float) (clone $base)->sum('total'),
            'total_paid' => (float) (clone $base)->sum('amount_paid'),
            'outstanding' => (float) (clone $base)->sum(DB::raw('total - amount_paid')),
            'overdue' => (clone $base)->where('status', InvoiceStatus::Overdue->value)->count(),
            'count' => (clone $base)->count(),
        ];
    }
}
