<?php

namespace App\Services\AI\Vendor;

use App\Models\AiAction;
use App\Models\BookingRequest;
use App\Models\MarketplaceVenue;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Runs the vendor copilot's actions — the things it does *for* the vendor rather
 * than just talks about: responding to a booking request, replying to a review.
 * Each type can (a) preview itself without touching anything and (b) execute once
 * the vendor approves. Approval (in the controller) is the single choke point
 * where anything actually changes, and every action is re-scoped to the vendor's
 * own records at execution time.
 */
class VendorActionExecutor
{
    /**
     * @return array<string, array{label:string}>
     */
    public static function catalog(): array
    {
        return [
            'vendor_respond_request' => ['label' => 'Respond to a booking request'],
            'vendor_reply_review' => ['label' => 'Reply to a review'],
        ];
    }

    public static function label(string $type): string
    {
        return self::catalog()[$type]['label'] ?? $type;
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::catalog());
    }

    /**
     * Queue an action for approval, building its human-readable title/summary
     * from a dry-run preview.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $attributes
     */
    public function queue(User $vendor, string $type, array $params, array $attributes = []): AiAction
    {
        $preview = $this->preview($vendor, $type, $params);

        return AiAction::create(array_merge([
            'user_id' => $vendor->id,
            'source' => 'chat',
            'type' => $type,
            'title' => $preview['title'],
            'summary' => $preview['summary'],
            'params' => $params,
            'status' => AiAction::STATUS_PENDING,
        ], $attributes));
    }

    /**
     * Describe what an action would do, without doing it.
     *
     * @param  array<string, mixed>  $params
     * @return array{title:string, summary:string, count:int}
     */
    public function preview(User $vendor, string $type, array $params): array
    {
        return match ($type) {
            'vendor_respond_request' => $this->previewRespondRequest($vendor, $params),
            'vendor_reply_review' => $this->previewReplyReview($vendor, $params),
            default => ['title' => self::label($type), 'summary' => 'Unknown action.', 'count' => 0],
        };
    }

    /** Perform a pending action and stamp the outcome onto it. */
    public function execute(AiAction $action): AiAction
    {
        $vendor = $action->user()->first();
        $params = $action->params ?? [];

        try {
            $result = match ($action->type) {
                'vendor_respond_request' => $this->runRespondRequest($vendor, $params),
                'vendor_reply_review' => $this->runReplyReview($vendor, $params),
                default => throw new \RuntimeException("Unknown action [{$action->type}]."),
            };

            $action->forceFill(['status' => AiAction::STATUS_DONE, 'result' => $result, 'executed_at' => now()])->save();
        } catch (\Throwable $e) {
            $action->forceFill(['status' => AiAction::STATUS_FAILED, 'error' => $e->getMessage(), 'executed_at' => now()])->save();
        }

        return $action;
    }

    // -----------------------------------------------------------------
    // Respond to a booking request
    // -----------------------------------------------------------------

    private function previewRespondRequest(User $vendor, array $params): array
    {
        $decision = $this->decision($params);
        $request = $this->resolveOpenRequest($vendor, (string) ($params['hint'] ?? ''));

        if (! $request) {
            return [
                'title' => 'Respond to a booking request',
                'summary' => $this->noOpenRequestMessage($vendor, (string) ($params['hint'] ?? '')),
                'count' => 0,
            ];
        }

        $verb = $decision === 'accept' ? 'Accept' : 'Decline';
        $who = $this->requestLabel($request);
        $note = ! empty($params['note']) ? " with the note “{$params['note']}”" : '';

        return [
            'title' => "{$verb} booking request · {$who}",
            'summary' => "{$verb} the booking request {$who}{$note}. The planner will be notified of your decision.",
            'count' => 1,
        ];
    }

    private function runRespondRequest(User $vendor, array $params): array
    {
        $decision = $this->decision($params);
        $request = $this->resolveOpenRequest($vendor, (string) ($params['hint'] ?? ''));
        abort_if($request === null, 422, 'No matching open booking request was found.');

        $status = $decision === 'accept' ? 'accepted' : 'declined';
        $request->update([
            'status' => $status,
            'response_note' => $params['note'] ?? null,
            'responded_at' => now(),
        ]);

        return ['request_id' => $request->id, 'status' => $status,
            'message' => ucfirst($status) . ' the booking request ' . $this->requestLabel($request) . '.'];
    }

    /**
     * The vendor's open (pending / info-requested) booking requests, resolved by a
     * fuzzy hint, or the single one when unambiguous.
     */
    private function resolveOpenRequest(User $vendor, string $hint): ?BookingRequest
    {
        $open = $this->scope(BookingRequest::query(), $vendor)
            ->whereIn('status', ['pending', 'info_requested'])
            ->with('planner:id,first_name,last_name')
            ->latest()
            ->get();

        return $this->matchOne($open, $hint, fn (BookingRequest $r) => trim(
            ($r->title ?? '') . ' ' . ($r->planner?->first_name ?? '') . ' ' . ($r->planner?->last_name ?? '')
        ));
    }

    private function noOpenRequestMessage(User $vendor, string $hint): string
    {
        $count = $this->scope(BookingRequest::query(), $vendor)->whereIn('status', ['pending', 'info_requested'])->count();

        if ($count === 0) {
            return 'You have no open booking requests to respond to right now.';
        }
        if ($hint !== '') {
            return "I couldn't find an open request matching “{$hint}”. You have {$count} open — try naming the planner or event.";
        }

        return "You have {$count} open requests — tell me which one (e.g. “accept the request from Sarah”).";
    }

    private function requestLabel(BookingRequest $request): string
    {
        $planner = trim(($request->planner?->first_name ?? '') . ' ' . ($request->planner?->last_name ?? ''));
        $title = $request->title ?: 'booking request';

        return $planner ? "“{$title}” from {$planner}" : "“{$title}”";
    }

    // -----------------------------------------------------------------
    // Reply to a review
    // -----------------------------------------------------------------

    private function previewReplyReview(User $vendor, array $params): array
    {
        $body = trim((string) ($params['body'] ?? ''));
        $review = $this->resolveReview($vendor, (string) ($params['hint'] ?? ''));

        if (! $review) {
            return ['title' => 'Reply to a review', 'summary' => $this->noReviewMessage($vendor), 'count' => 0];
        }
        if ($body === '') {
            return ['title' => 'Reply to a review', 'summary' => 'What would you like the reply to say?', 'count' => 0];
        }

        return [
            'title' => 'Reply to a ' . (int) $review->overall_rating . '★ review',
            'summary' => "Post this public reply to the {$review->overall_rating}★ review: “{$body}”.",
            'count' => 1,
        ];
    }

    private function runReplyReview(User $vendor, array $params): array
    {
        $body = trim((string) ($params['body'] ?? ''));
        abort_if($body === '', 422, 'A reply body is required.');

        $review = $this->resolveReview($vendor, (string) ($params['hint'] ?? ''));
        abort_if($review === null, 422, 'No matching review awaiting a reply was found.');

        $reply = $review->replies()->create([
            'user_id' => $vendor->id,
            'body' => mb_substr($body, 0, 2000),
        ]);

        return ['review_id' => $review->id, 'reply_id' => $reply->id, 'message' => 'Posted your reply to the review.'];
    }

    /** A published review the vendor owns and hasn't replied to yet. */
    private function resolveReview(User $vendor, string $hint): ?Review
    {
        $reviews = $this->scope(Review::query(), $vendor)
            ->where('status', 'published')
            ->withCount('replies')
            ->with('reviewer:id,first_name,last_name')
            ->get()
            ->where('replies_count', 0);

        return $this->matchOne($reviews, $hint, fn (Review $r) => trim(
            ($r->title ?? '') . ' ' . ($r->reviewer?->first_name ?? '') . ' ' . ($r->reviewer?->last_name ?? '')
        ));
    }

    private function noReviewMessage(User $vendor): string
    {
        return 'You have no reviews awaiting a reply right now.';
    }

    // -----------------------------------------------------------------
    // Shared helpers
    // -----------------------------------------------------------------

    /**
     * Pick a single record from candidates: by fuzzy hint if given, else the only
     * one when unambiguous.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     * @param  Collection<int, T>  $candidates
     * @param  callable(T):string  $haystack
     * @return T|null
     */
    private function matchOne(Collection $candidates, string $hint, callable $haystack): mixed
    {
        $hint = mb_strtolower(trim($hint));

        if ($hint === '') {
            return $candidates->count() === 1 ? $candidates->first() : null;
        }

        return $candidates->first(fn ($c) => str_contains(mb_strtolower($haystack($c)), $hint))
            ?? ($candidates->count() === 1 ? $candidates->first() : null);
    }

    private function decision(array $params): string
    {
        return ($params['decision'] ?? 'accept') === 'decline' ? 'decline' : 'accept';
    }

    /**
     * Constrain a provider-owned query to this vendor: their vendor_id, or a venue
     * they own.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private function scope(\Illuminate\Database\Eloquent\Builder $query, User $vendor): \Illuminate\Database\Eloquent\Builder
    {
        $venueIds = MarketplaceVenue::where('owner_id', $vendor->id)->pluck('id')->all();

        return $query->where(fn ($q) => $q
            ->where('vendor_id', $vendor->id)
            ->when($venueIds, fn ($qq) => $qq->orWhereIn('venue_id', $venueIds)));
    }
}
