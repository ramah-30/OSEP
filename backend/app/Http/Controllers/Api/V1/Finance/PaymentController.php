<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\PaymentDirection;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ScheduleStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Receipt;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'direction' => ['nullable', Rule::enum(PaymentDirection::class)],
            'event_id' => ['nullable', 'integer'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        $payments = Payment::where('planner_id', $request->user()->id)
            ->when($filters['direction'] ?? null, fn ($q, $d) => $q->where('direction', $d))
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->when($filters['invoice_id'] ?? null, fn ($q, $id) => $q->where('invoice_id', $id))
            ->with(['invoice', 'event', 'vendorAssignment', 'receipt'])
            ->latest('paid_at')
            ->get();

        return $this->success([
            'payments' => PaymentResource::collection($payments),
            'summary' => $this->summary($request),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::enum(PaymentDirection::class)],
            'event_id' => ['nullable', 'integer'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'vendor_assigned_id' => ['nullable', 'integer', 'exists:vendors_assigned,id'],
            'payment_schedule_id' => ['nullable', 'integer', 'exists:payment_schedules,id'],
            'party_name' => ['nullable', 'string', 'max:150'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'transaction_ref' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $event = $this->ownedEvent($request, $data['event_id'] ?? null);
        $invoice = null;
        if (! empty($data['invoice_id'])) {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $this->ensureOwned($request, $invoice);
            $event ??= $invoice->event;
        }

        $payment = DB::transaction(function () use ($data, $request, $invoice) {
            $payment = Payment::create([
                ...$data,
                'payment_number' => $this->nextNumber('PAY', Payment::class, 'payment_number'),
                'planner_id' => $request->user()->id,
                'event_id' => $data['event_id'] ?? $invoice?->event_id,
                'currency' => $data['currency'] ?? $invoice?->currency ?? 'TZS',
                'status' => PaymentStatus::Completed->value,
                'recorded_by' => $request->user()->id,
            ]);

            // Incoming payments settle an invoice and mint a receipt.
            if ($payment->direction === PaymentDirection::Incoming) {
                if ($invoice) {
                    $invoice->recalculatePaid();
                }
                $this->issueReceipt($payment, $invoice, $request->user()->id);
            }

            // Paying off a scheduled installment marks it paid.
            if ($payment->payment_schedule_id) {
                PaymentSchedule::where('id', $payment->payment_schedule_id)
                    ->update(['status' => ScheduleStatus::Paid->value, 'paid_at' => $payment->paid_at]);
            }

            return $payment;
        });

        $this->logFinance($event, $request, 'payment_recorded', "recorded {$payment->direction->label()} {$payment->payment_number}", $payment);

        return $this->created([
            'payment' => new PaymentResource($payment->load(['invoice', 'event', 'vendorAssignment', 'receipt'])),
        ], 'Payment recorded.');
    }

    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $this->ensureOwned($request, $payment);
        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice) {
            $payment->receipt()->delete();
            $payment->delete();
            $invoice?->recalculatePaid();
        });

        return $this->success(null, 'Payment removed.');
    }

    private function issueReceipt(Payment $payment, ?Invoice $invoice, int $plannerId): void
    {
        Receipt::create([
            'receipt_number' => $this->nextNumber('RCP', Receipt::class, 'receipt_number'),
            'payment_id' => $payment->id,
            'planner_id' => $plannerId,
            'client_id' => $invoice?->client_id,
            'event_id' => $payment->event_id,
            'invoice_id' => $invoice?->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'issued_at' => now(),
        ]);
    }

    /**
     * @return array<string, float>
     */
    private function summary(Request $request): array
    {
        $base = Payment::where('planner_id', $request->user()->id)->where('status', PaymentStatus::Completed->value);

        return [
            'received' => (float) (clone $base)->where('direction', PaymentDirection::Incoming->value)->sum('amount'),
            'paid_out' => (float) (clone $base)->where('direction', PaymentDirection::Outgoing->value)->sum('amount'),
        ];
    }
}
