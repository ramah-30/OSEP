<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiFeedback;
use App\Models\AiGeneratedDocument;
use App\Models\AiMessage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Planner feedback on AI output. A rating is one row per planner per subject
 * (message or document), upserted so the thumb toggles, and cleared on request.
 * A summary powers the AI quality view.
 */
class FeedbackController extends Controller
{
    use ApiResponse;

    /** Set (or update) the planner's rating on a message or document. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => ['required', 'in:message,document'],
            'subject_id' => ['required', 'integer'],
            'rating' => ['required', 'in:up,down'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $eventId = $this->authorizeSubject($user->id, $data['subject_type'], $data['subject_id']);

        $feedback = AiFeedback::updateOrCreate(
            [
                'user_id' => $user->id,
                'subject_type' => $data['subject_type'],
                'subject_id' => $data['subject_id'],
            ],
            [
                'event_id' => $eventId,
                'rating' => $data['rating'],
                'reason' => $data['reason'] ?? null,
            ],
        );

        return $this->success([
            'rating' => $feedback->rating->value,
            'reason' => $feedback->reason,
        ], 'Thanks for the feedback.');
    }

    /** Clear the planner's rating on a subject (toggle off). */
    public function destroy(Request $request, string $subjectType, int $subjectId): JsonResponse
    {
        abort_unless(in_array($subjectType, ['message', 'document'], true), 404);

        AiFeedback::where('user_id', $request->user()->id)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->delete();

        return $this->success(['rating' => null], 'Feedback removed.');
    }

    /** Aggregate quality signal across everything the planner has rated. */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        $all = AiFeedback::where('user_id', $user->id)->get();
        $up = $all->where('rating', \App\Enums\FeedbackRating::Up)->count();
        $down = $all->where('rating', \App\Enums\FeedbackRating::Down)->count();
        $total = $all->count();

        // Resolve subject titles for the most recent negative feedback so the
        // planner can see what fell short and why.
        $recentDown = $all->filter(fn ($f) => $f->rating === \App\Enums\FeedbackRating::Down && $f->reason)
            ->sortByDesc('created_at')
            ->take(5)
            ->map(fn ($f) => [
                'subject_type' => $f->subject_type,
                'subject' => $this->subjectLabel($f->subject_type, $f->subject_id),
                'reason' => $f->reason,
                'at' => $f->created_at?->toIso8601String(),
            ])
            ->values();

        return $this->success([
            'up' => $up,
            'down' => $down,
            'total' => $total,
            'positive_rate' => $total > 0 ? (int) round($up / $total * 100) : null,
            'recent_negative' => $recentDown,
        ]);
    }

    /**
     * Confirm the planner owns the rated subject and return its event id (for
     * aggregation). 404s on anything they may not touch.
     */
    private function authorizeSubject(int $userId, string $type, int $id): ?int
    {
        if ($type === 'message') {
            $message = AiMessage::with('conversation:id,user_id,event_id')->find($id);
            abort_unless($message && $message->conversation && $message->conversation->user_id === $userId, 404);

            return $message->conversation->event_id;
        }

        $document = AiGeneratedDocument::find($id);
        abort_unless($document && $document->user_id === $userId, 404);

        return $document->event_id;
    }

    private function subjectLabel(string $type, int $id): string
    {
        if ($type === 'document') {
            return AiGeneratedDocument::whereKey($id)->value('title') ?? 'Document';
        }

        $content = AiMessage::whereKey($id)->value('content');

        return $content ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 60) : 'Message';
    }
}
