<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\Finance\Concerns\HandlesFinance;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    use ApiResponse, HandlesFinance;

    public function index(Request $request): JsonResponse
    {
        $receipts = Receipt::where('planner_id', $request->user()->id)
            ->when($request->query('event_id'), fn ($q, $id) => $q->where('event_id', $id))
            ->with(['payment', 'client', 'event'])
            ->latest('issued_at')
            ->get();

        return $this->success([
            'receipts' => ReceiptResource::collection($receipts),
        ]);
    }

    public function show(Request $request, Receipt $receipt): JsonResponse
    {
        $this->ensureOwned($request, $receipt);

        return $this->success([
            'receipt' => new ReceiptResource($receipt->load(['payment', 'client', 'event', 'invoice'])),
        ]);
    }
}
