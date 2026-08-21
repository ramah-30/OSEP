<?php

namespace App\Services\AI\Vendor;

use App\Models\BookingRequest;
use App\Models\Contract;
use App\Models\MarketplaceVenue;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Assembles the structured, permission-filtered snapshot of a vendor's business
 * that the vendor copilot reasons over - booking pipeline, quotations, contracts
 * and revenue, reviews, availability and storefront completeness. Every figure
 * is scoped to what this vendor owns (directly via vendor_id, or through a venue
 * they own), so the AI can only ever see the vendor's own book of business.
 */
class VendorContextBuilder
{
    /** Quotation states that are "live" - sent to a planner, not yet resolved. */
    private const QUOTE_OPEN = ['sent', 'negotiating'];

    /**
     * @return array<string, mixed>
     */
    public function forVendor(User $vendor): array
    {
        $venueIds = MarketplaceVenue::where('owner_id', $vendor->id)->pluck('id')->all();

        return array_filter([
            'vendor' => $this->profileSummary($vendor),
            'requests' => $this->requestSummary($vendor, $venueIds),
            'quotations' => $this->quotationSummary($vendor, $venueIds),
            'contracts' => $this->contractSummary($vendor, $venueIds),
            'reviews' => $this->reviewSummary($vendor, $venueIds),
            'availability' => $this->availabilitySummary($vendor),
            'storefront' => $this->storefrontSummary($vendor),
        ], fn ($v) => $v !== null);
    }

    /**
     * Scope a query on a provider-owned record to this vendor: rows tagged with
     * their vendor_id, or attached to a venue they own.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  array<int, int>  $venueIds
     */
    private function scope(Builder $query, User $vendor, array $venueIds): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('vendor_id', $vendor->id)
            ->when($venueIds, fn (Builder $qq) => $qq->orWhereIn('venue_id', $venueIds)));
    }

    /**
     * @return array<string, mixed>
     */
    private function profileSummary(User $vendor): array
    {
        $p = $vendor->vendorProfile;

        return [
            'business_name' => $p?->business_name ?: $vendor->full_name,
            'category' => $p?->category,
            'verification_status' => $p?->verification_status,
            'verification_level' => $p?->verification_level,
            'years_in_business' => $p?->years_in_business !== null ? (int) $p->years_in_business : null,
            'rating' => $p?->rating !== null ? (float) $p->rating : null,
            'reviews_count' => (int) ($p?->reviews_count ?? 0),
            'profile_views' => (int) ($p?->profile_views ?? 0),
        ];
    }

    /**
     * @param  array<int, int>  $venueIds
     * @return array<string, mixed>
     */
    private function requestSummary(User $vendor, array $venueIds): array
    {
        $requests = $this->scope(BookingRequest::query(), $vendor, $venueIds)
            ->with('planner:id,first_name,last_name')
            ->latest()
            ->get();

        $open = $this->whereStatus($requests, ['pending', 'info_requested']);
        $oldestPending = $open->min('created_at');

        return [
            'total' => $requests->count(),
            'pending' => $this->whereStatus($requests, 'pending')->count(),
            'info_requested' => $this->whereStatus($requests, 'info_requested')->count(),
            'accepted' => $this->whereStatus($requests, 'accepted')->count(),
            'declined' => $this->whereStatus($requests, 'declined')->count(),
            'open' => $open->count(),
            'oldest_pending_days' => $oldestPending
                ? (int) round(Carbon::parse($oldestPending)->diffInDays(Carbon::now()))
                : null,
            'open_list' => $open->take(6)->map(fn (BookingRequest $r) => [
                'title' => $r->title ?: 'Booking request',
                'planner' => trim(($r->planner->first_name ?? '') . ' ' . ($r->planner->last_name ?? '')) ?: null,
                'event_date' => $r->event_date ? Carbon::parse($r->event_date)->toFormattedDateString() : null,
                'budget' => (float) $r->budget,
                'status' => $this->value($r->status),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, int>  $venueIds
     * @return array<string, mixed>
     */
    private function quotationSummary(User $vendor, array $venueIds): array
    {
        $quotes = $this->scope(Quotation::query(), $vendor, $venueIds)->get();

        $accepted = $this->whereStatus($quotes, 'accepted')->count();
        $rejected = $this->whereStatus($quotes, 'rejected')->count();
        $resolved = $accepted + $rejected;

        $soon = Carbon::now()->addDays(7);
        $expiring = $this->whereStatus($quotes, self::QUOTE_OPEN)
            ->filter(fn (Quotation $q) => $q->expires_at
                && Carbon::parse($q->expires_at)->betweenIncluded(Carbon::now(), $soon));

        return [
            'total' => $quotes->count(),
            'draft' => $this->whereStatus($quotes, 'draft')->count(),
            'open' => $this->whereStatus($quotes, self::QUOTE_OPEN)->count(),
            'accepted' => $accepted,
            'rejected' => $rejected,
            'expired' => $this->whereStatus($quotes, 'expired')->count(),
            'win_rate' => $resolved > 0 ? (int) round($accepted / $resolved * 100) : null,
            'expiring_soon' => $expiring->count(),
            'expiring_list' => $expiring->take(5)->map(fn (Quotation $q) => [
                'reference' => $q->reference,
                'total' => (float) $q->total,
                'expires' => Carbon::parse($q->expires_at)->toFormattedDateString(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<int, int>  $venueIds
     * @return array<string, mixed>
     */
    private function contractSummary(User $vendor, array $venueIds): array
    {
        $contracts = $this->scope(Contract::query(), $vendor, $venueIds)->get();

        return [
            'total' => $contracts->count(),
            'awaiting_signature' => $this->whereStatus($contracts, ['draft', 'sent'])->count(),
            'active' => $this->whereStatus($contracts, ['signed', 'active'])->count(),
            'completed' => $this->whereStatus($contracts, 'completed')->count(),
            'revenue' => (float) $this->whereStatus($contracts, ['active', 'completed'])->sum('amount'),
            'pipeline_value' => (float) $this->whereStatus($contracts, ['draft', 'sent', 'signed'])->sum('amount'),
        ];
    }

    /**
     * @param  array<int, int>  $venueIds
     * @return array<string, mixed>
     */
    private function reviewSummary(User $vendor, array $venueIds): array
    {
        $reviews = $this->scope(Review::query(), $vendor, $venueIds)
            ->where('status', 'published')
            ->withCount('replies')
            ->latest()
            ->get();

        $unreplied = $reviews->where('replies_count', 0);
        $unrepliedNegative = $unreplied->filter(fn (Review $r) => (float) $r->overall_rating <= 3);

        return [
            'total' => $reviews->count(),
            'average_rating' => $reviews->count() ? round((float) $reviews->avg('overall_rating'), 1) : null,
            'unreplied' => $unreplied->count(),
            'unreplied_negative' => $unrepliedNegative->count(),
            'recent_unreplied' => $unreplied->take(4)->map(fn (Review $r) => [
                'rating' => (float) $r->overall_rating,
                'title' => $r->title,
                'comment' => $r->comment ? \Illuminate\Support\Str::limit($r->comment, 120) : null,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function availabilitySummary(User $vendor): array
    {
        $today = Carbon::today();
        $horizon = $today->copy()->addDays(60);

        $rows = VendorAvailability::where('vendor_id', $vendor->id)
            ->whereBetween('date', [$today, $horizon])
            ->get();

        $nextAvailable = VendorAvailability::where('vendor_id', $vendor->id)
            ->where('status', 'available')
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->value('date');

        return [
            'has_calendar' => VendorAvailability::where('vendor_id', $vendor->id)->exists(),
            'available_upcoming' => $this->whereStatus($rows, 'available')->count(),
            'busy_upcoming' => $this->whereStatus($rows, ['busy', 'unavailable'])->count(),
            'next_available' => $nextAvailable ? Carbon::parse($nextAvailable)->toFormattedDateString() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storefrontSummary(User $vendor): array
    {
        $p = $vendor->vendorProfile;
        $services = $vendor->vendorServices()->count();
        $packages = $vendor->vendorPackages()->count();
        $portfolio = $vendor->vendorPortfolios()->count();

        $missing = array_values(array_filter([
            $services === 0 ? 'services' : null,
            $packages === 0 ? 'packages' : null,
            $portfolio === 0 ? 'portfolio images' : null,
            empty($p?->description) ? 'business description' : null,
            empty($p?->logo_url) ? 'logo' : null,
        ]));

        return [
            'services' => $services,
            'packages' => $packages,
            'portfolio' => $portfolio,
            'missing' => $missing,
            'complete' => empty($missing),
        ];
    }

    /** Normalise an enum-or-string status to its string value. */
    private function value(mixed $status): ?string
    {
        if ($status instanceof \BackedEnum) {
            return $status->value;
        }

        return $status !== null ? (string) $status : null;
    }

    /**
     * Filter a collection by status value, tolerating models that cast `status`
     * to a backed enum as well as those that keep it a plain string.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>  $collection
     * @param  array<int, string>|string  $statuses
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function whereStatus(\Illuminate\Support\Collection $collection, array|string $statuses): \Illuminate\Support\Collection
    {
        $wanted = (array) $statuses;

        return $collection->filter(fn ($model) => in_array($this->value($model->status), $wanted, true));
    }
}
