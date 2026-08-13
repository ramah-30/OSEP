<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\V1\Vendor\Concerns\ScopesToProvider;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\Quotation;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Vendor business insights. Trends are bucketed over the last 6 months; the
 * response shape is deliberately chart-ready so the frontend SVG charts can map
 * it directly. Phase 7 AI can layer predictions on the same series.
 */
class AnalyticsController extends Controller
{
    use ApiResponse, ScopesToProvider;

    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();
        $months = $this->lastMonths(6);

        $requests = $this->scopeToProvider(BookingRequest::query(), $vendor)->get(['status', 'created_at']);
        $contracts = $this->scopeToProvider(Contract::query(), $vendor)
            ->whereIn('status', ['active', 'completed'])->get(['amount', 'created_at']);
        $reviews = $this->scopeToProvider(Review::query(), $vendor)->get(['overall_rating']);

        $totalRequests = $requests->count();
        $accepted = $requests->where('status.value', 'accepted')->count();

        $responded = $this->scopeToProvider(BookingRequest::query(), $vendor)
            ->whereNotNull('responded_at')->get(['created_at', 'responded_at']);
        $avgResponseHours = $responded->count()
            ? round($responded->avg(fn ($r) => $r->created_at->diffInHours($r->responded_at)), 1)
            : null;

        return $this->success([
            'booking_trends' => $months->map(fn ($m) => [
                'month' => $m['label'],
                'requests' => $requests->filter(fn ($r) => $r->created_at->isSameMonth($m['date']))->count(),
            ])->values(),
            'revenue_by_month' => $months->map(fn ($m) => [
                'month' => $m['label'],
                'revenue' => (float) $contracts->filter(fn ($c) => $c->created_at->isSameMonth($m['date']))->sum('amount'),
            ])->values(),
            'conversion_rate' => $totalRequests ? round($accepted / $totalRequests * 100, 1) : 0.0,
            'avg_response_hours' => $avgResponseHours,
            'review_distribution' => collect(range(5, 1))->map(fn ($star) => [
                'stars' => $star,
                'count' => $reviews->filter(fn ($r) => (int) round($r->overall_rating) === $star)->count(),
            ])->values(),
            'popular_services' => $this->popularServices($vendor->id),
            'totals' => [
                'requests' => $totalRequests,
                'accepted' => $accepted,
                'quotations' => $this->scopeToProvider(Quotation::query(), $vendor)->count(),
                'revenue' => (float) $contracts->sum('amount'),
            ],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name:string, count:int}>
     */
    private function popularServices(int $vendorId): \Illuminate\Support\Collection
    {
        return Quotation::query()
            ->where('vendor_id', $vendorId)
            ->join('quotation_items', 'quotation_items.quotation_id', '=', 'quotations.id')
            ->selectRaw('quotation_items.description as name, count(*) as count')
            ->groupBy('quotation_items.description')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->count]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{date: Carbon, label: string}>
     */
    private function lastMonths(int $count): \Illuminate\Support\Collection
    {
        return collect(range($count - 1, 0))->map(function ($i) {
            $date = Carbon::now()->startOfMonth()->subMonths($i);

            return ['date' => $date, 'label' => $date->format('M')];
        });
    }
}
