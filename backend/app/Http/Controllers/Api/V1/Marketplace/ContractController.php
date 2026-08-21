<?php

namespace App\Http\Controllers\Api\V1\Marketplace;

use App\Enums\ContractStatus;
use App\Enums\MobileNetwork;
use App\Enums\PaymentDirection;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Http\Resources\PaymentResource;
use App\Models\Contract;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Planner view of contracts. The planner signs a contract the vendor has sent;
 * status beyond that (active/completed) is driven from the vendor side.
 */
class ContractController extends Controller
{
    use ApiResponse, HandlesFinance;

    private const DECLINE_NUMBER = '0000000000';

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

        $ownerId = $contract->providerId();
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

    /**
     * Simulated mobile-money payment to the vendor/venue-owner behind this
     * contract. Mirrors ClientInvoiceController::pay() - same fake-decline
     * number, same shape - but settles a Contract's balance instead of an
     * Invoice's.
     */
    public function pay(Request $request, Contract $contract, ActivityLogger $activity): JsonResponse
    {
        $this->authorizeOwner($request, $contract);
        abort_unless(
            in_array($contract->status, [ContractStatus::Signed, ContractStatus::Active, ContractStatus::Completed], true),
            422,
            'Only a signed contract can be paid.',
        );
        abort_unless($contract->balance() > 0, 422, 'This contract has nothing left to pay.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$contract->balance()],
            'payer_phone' => ['required', 'string', 'max:20'],
            'network' => ['required', Rule::enum(MobileNetwork::class)],
        ]);

        $declined = trim($data['payer_phone']) === self::DECLINE_NUMBER;

        $payment = DB::transaction(function () use ($data, $request, $contract, $declined) {
            $payment = Payment::create([
                'payment_number' => $this->nextNumber('PAY', Payment::class, 'payment_number'),
                'planner_id' => $contract->planner_id,
                'event_id' => $contract->event_id,
                'contract_id' => $contract->id,
                'direction' => PaymentDirection::Outgoing->value,
                'party_name' => $contract->providerName(),
                'method' => PaymentMethod::MobileMoney->value,
                'amount' => $data['amount'],
                'currency' => $contract->currency,
                'transaction_ref' => 'SIM-'.strtoupper(Str::random(10)),
                'network' => $data['network'],
                'payer_phone' => $data['payer_phone'],
                'status' => $declined ? PaymentStatus::Failed->value : PaymentStatus::Completed->value,
                'paid_at' => now()->toDateString(),
                'recorded_by' => $request->user()->id,
            ]);

            if (! $declined) {
                $contract->recalculatePaid();

                $providerId = $contract->providerId();

                Receipt::create([
                    'receipt_number' => $this->nextNumber('RCP', Receipt::class, 'receipt_number'),
                    'payment_id' => $payment->id,
                    'planner_id' => $contract->planner_id,
                    'vendor_id' => $providerId,
                    'event_id' => $contract->event_id,
                    'contract_id' => $contract->id,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'issued_at' => now(),
                ]);

                if ($providerId) {
                    Notification::create([
                        'user_id' => $providerId,
                        'type' => 'payment_sent',
                        'title' => 'Payment received',
                        'message' => "{$request->user()->full_name} paid {$payment->currency} ".number_format((float) $payment->amount, 2)." towards contract {$contract->reference}.",
                        'data' => ['contract_id' => $contract->id, 'payment_id' => $payment->id],
                    ]);
                }
            }

            return $payment;
        });

        if (! $declined && $event = $contract->event()->first()) {
            $activity->log(
                $event,
                $request->user(),
                'contract_payment',
                "Paid {$payment->currency} ".number_format((float) $payment->amount, 2)." towards contract {$contract->reference}.",
                $payment,
                visibleToClient: true,
            );
        }

        return $this->success([
            'payment' => new PaymentResource($payment->load('receipt')),
            'contract' => new ContractResource($contract->refresh()),
        ], $declined ? 'Payment declined by the mobile network.' : 'Payment successful.');
    }

    private function authorizeOwner(Request $request, Contract $contract): void
    {
        abort_unless($contract->planner_id === $request->user()->id, 404);
    }
}
