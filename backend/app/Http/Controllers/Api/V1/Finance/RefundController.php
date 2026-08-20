<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Payment;
use App\Models\Refund;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $refunds = Refund::where('planner_id', $request->user()->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->with(['payment', 'event'])
            ->latest()
            ->get();

        return $this->success([
            'refunds' => RefundResource::collection($refunds),
            'summary' => [
                'requested' => (float) Refund::where('planner_id', $request->user()->id)->whereIn('status', [RefundStatus::Requested->value, RefundStatus::Approved->value])->sum('amount'),
                'processed' => (float) Refund::where('planner_id', $request->user()->id)->where('status', RefundStatus::Processed->value)->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_id' => ['nullable', 'integer', 'exists:payments,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'event_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $event = $this->ownedEvent($request, $data['event_id'] ?? null);

        $payment = null;
        if (! empty($data['payment_id'])) {
            $payment = Payment::findOrFail($data['payment_id']);
            $this->ensureOwned($request, $payment);
            $event ??= $payment->event;
        }

        $refund = Refund::create([
            ...$data,
            'refund_number' => $this->nextNumber('REF', Refund::class, 'refund_number'),
            'planner_id' => $request->user()->id,
            'event_id' => $data['event_id'] ?? $payment?->event_id,
            'client_id' => $payment?->invoice?->client_id,
            'currency' => $data['currency'] ?? $payment?->currency ?? 'TZS',
            'status' => RefundStatus::Requested->value,
        ]);

        $this->logFinance($event, $request, 'refund_requested', "requested refund {$refund->refund_number}", $refund);

        return $this->created([
            'refund' => new RefundResource($refund->load(['payment', 'event'])),
        ], 'Refund requested.');
    }

    public function transition(Request $request, Refund $refund): JsonResponse
    {
        $this->ensureOwned($request, $refund);

        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'process', 'reject'])],
        ]);

        [$status, $extra, $verb] = match ($data['action']) {
            'approve' => [RefundStatus::Approved, ['approved_by' => $request->user()->id, 'approved_at' => now()], 'approved'],
            'process' => [RefundStatus::Processed, ['processed_at' => now()], 'processed'],
            'reject' => [RefundStatus::Rejected, [], 'rejected'],
        };

        $refund->fill(['status' => $status->value, ...$extra])->save();

        // A processed refund flips the linked payment to refunded.
        if ($status === RefundStatus::Processed && $refund->payment) {
            $refund->payment->update(['status' => PaymentStatus::Refunded->value]);
            $refund->payment->invoice?->recalculatePaid();
        }

        $this->logFinance($refund->event, $request, 'refund_'.$data['action'], "refund {$refund->refund_number} {$verb}", $refund);

        return $this->success([
            'refund' => new RefundResource($refund->load(['payment', 'event'])),
        ], "Refund {$verb}.");
    }
}
