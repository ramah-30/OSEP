<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilitySlotResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A vendor's own booking calendar. The planner-facing read of the same data is
 * served through the vendor storefront.
 */
class AvailabilityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $slots = $request->user()->vendorAvailability()
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('date', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('date', '<=', $to))
            ->orderBy('date')->get();

        return $this->success([
            'availability' => AvailabilitySlotResource::collection($slots),
        ]);
    }

    /** Upsert one or more calendar days in a single call. */
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.date' => ['required', 'date'],
            'slots.*.status' => ['required', Rule::in(['available', 'reserved', 'fully_booked', 'on_leave'])],
            'slots.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['slots'] as $slot) {
            $request->user()->vendorAvailability()->updateOrCreate(
                ['date' => $slot['date']],
                ['status' => $slot['status'], 'note' => $slot['note'] ?? null],
            );
        }

        return $this->success([
            'availability' => AvailabilitySlotResource::collection(
                $request->user()->vendorAvailability()->orderBy('date')->get()
            ),
        ], 'Availability updated.');
    }

    public function destroy(Request $request, string $date): JsonResponse
    {
        $request->user()->vendorAvailability()->whereDate('date', $date)->delete();

        return $this->success(null, 'Availability cleared.');
    }
}
