<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\ScheduleStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentScheduleResource;
use App\Models\Invoice;
use App\Models\PaymentSchedule;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'invoice_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
        ]);

        $this->refreshOverdue($request);

        $schedules = PaymentSchedule::where('planner_id', $request->user()->id)
            ->when($filters['invoice_id'] ?? null, fn ($q, $id) => $q->where('invoice_id', $id))
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('event_id', $id))
            ->with('invoice')
            ->orderBy('sort_order')
            ->orderBy('due_date')
            ->get();

        return $this->success([
            'schedules' => PaymentScheduleResource::collection($schedules),
        ]);
    }

    /** Replace an invoice's installment plan with the supplied rows. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'installments' => ['required', 'array', 'min:1'],
            'installments.*.label' => ['required', 'string', 'max:100'],
            'installments.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'installments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'installments.*.due_date' => ['nullable', 'date'],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $this->ensureOwned($request, $invoice);

        DB::transaction(function () use ($invoice, $data, $request) {
            PaymentSchedule::where('invoice_id', $invoice->id)->delete();

            foreach (array_values($data['installments']) as $index => $row) {
                $amount = $row['amount']
                    ?? (isset($row['percentage']) ? round((float) $invoice->total * (float) $row['percentage'] / 100, 2) : 0);

                PaymentSchedule::create([
                    'planner_id' => $request->user()->id,
                    'event_id' => $invoice->event_id,
                    'invoice_id' => $invoice->id,
                    'label' => $row['label'],
                    'percentage' => $row['percentage'] ?? null,
                    'amount' => $amount,
                    'currency' => $invoice->currency,
                    'due_date' => $row['due_date'] ?? null,
                    'status' => ScheduleStatus::Pending->value,
                    'sort_order' => $index,
                ]);
            }
        });

        $schedules = PaymentSchedule::where('invoice_id', $invoice->id)->orderBy('sort_order')->get();

        return $this->created([
            'schedules' => PaymentScheduleResource::collection($schedules),
        ], 'Payment schedule saved.');
    }

    public function update(Request $request, PaymentSchedule $schedule): JsonResponse
    {
        $this->ensureOwned($request, $schedule);

        $data = $request->validate([
            'label' => ['sometimes', 'string', 'max:100'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(ScheduleStatus::class)],
        ]);

        $schedule->fill($data);
        if (($data['status'] ?? null) === ScheduleStatus::Paid->value) {
            $schedule->paid_at = now()->toDateString();
        }
        $schedule->save();

        return $this->success([
            'schedule' => new PaymentScheduleResource($schedule->load('invoice')),
        ], 'Installment updated.');
    }

    public function destroy(Request $request, PaymentSchedule $schedule): JsonResponse
    {
        $this->ensureOwned($request, $schedule);
        $schedule->delete();

        return $this->success(null, 'Installment removed.');
    }

    private function refreshOverdue(Request $request): void
    {
        PaymentSchedule::where('planner_id', $request->user()->id)
            ->whereIn('status', [ScheduleStatus::Pending->value, ScheduleStatus::Scheduled->value])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now())
            ->update(['status' => ScheduleStatus::Overdue->value]);
    }
}
