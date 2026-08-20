<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MobileNetwork;
use App\Enums\PaymentDirection;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Receipt;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The client's own view of their invoices, plus the simulated mobile-money
 * "pay" action. A fixed fake number (0000000000) always declines, matching
 * the simulator's convention (see also Marketplace\ContractController::pay).
 */
class ClientInvoiceController extends Controller
{
    use ApiResponse, HandlesFinance;

    private const DECLINE_NUMBER = '0000000000';

    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::where('client_id', $request->user()->id)
            ->with(['event', 'planner'])
            ->latest()
            ->get();

        return $this->success([
            'invoices' => InvoiceResource::collection($invoices),
        ]);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeClient($request, $invoice);

        return $this->success([
            'invoice' => new InvoiceResource($invoice->load(['items', 'event', 'planner', 'payments'])),
        ]);
    }

    public function pay(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeClient($request, $invoice);
        abort_unless($invoice->status->isCollectable(), 422, 'This invoice has nothing left to collect.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->balance()],
            'payer_phone' => ['required', 'string', 'max:20'],
            'network' => ['required', Rule::enum(MobileNetwork::class)],
        ]);

        $declined = trim($data['payer_phone']) === self::DECLINE_NUMBER;

        $payment = DB::transaction(function () use ($data, $request, $invoice, $declined) {
            $payment = Payment::create([
                'payment_number' => $this->nextNumber('PAY', Payment::class, 'payment_number'),
                'planner_id' => $invoice->planner_id,
                'event_id' => $invoice->event_id,
                'invoice_id' => $invoice->id,
                'direction' => PaymentDirection::Incoming->value,
                'party_name' => $request->user()->full_name,
                'method' => PaymentMethod::MobileMoney->value,
                'amount' => $data['amount'],
                'currency' => $invoice->currency,
                'transaction_ref' => 'SIM-'.strtoupper(Str::random(10)),
                'network' => $data['network'],
                'payer_phone' => $data['payer_phone'],
                'status' => $declined ? PaymentStatus::Failed->value : PaymentStatus::Completed->value,
                'paid_at' => now()->toDateString(),
                'recorded_by' => $request->user()->id,
            ]);

            if (! $declined) {
                $invoice->recalculatePaid();

                Receipt::create([
                    'receipt_number' => $this->nextNumber('RCP', Receipt::class, 'receipt_number'),
                    'payment_id' => $payment->id,
                    'planner_id' => $invoice->planner_id,
                    'client_id' => $invoice->client_id,
                    'event_id' => $invoice->event_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'issued_at' => now(),
                ]);

                Notification::create([
                    'user_id' => $invoice->planner_id,
                    'type' => 'payment_received',
                    'title' => 'Payment received',
                    'message' => "{$request->user()->full_name} paid {$payment->currency} ".number_format((float) $payment->amount, 2)." towards invoice {$invoice->invoice_number}.",
                    'data' => ['invoice_id' => $invoice->id, 'payment_id' => $payment->id],
                ]);

                $this->logFinance(
                    $invoice->event,
                    $request,
                    'payment_received',
                    "paid {$payment->currency} ".number_format((float) $payment->amount, 2)." towards invoice {$invoice->invoice_number}",
                    $payment,
                    visibleToClient: true,
                );
            }

            return $payment;
        });

        return $this->success([
            'payment' => new PaymentResource($payment->load('receipt')),
            'invoice' => new InvoiceResource($invoice->refresh()),
        ], $declined ? 'Payment declined by the mobile network.' : 'Payment successful.');
    }

    private function authorizeClient(Request $request, Invoice $invoice): void
    {
        abort_unless($invoice->client_id === $request->user()->id, 404);
    }
}
