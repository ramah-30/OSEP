<?php

namespace App\Services\AI;

use App\Models\AiKnowledgeDocument;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Lightweight, dependency-free retrieval over the planner's knowledge base. It
 * scores each candidate note by keyword overlap with the question (title matches
 * weighted highest), returns the best few with a focused snippet, and lets the
 * providers cite them. No embeddings or external service required — the copilot
 * can ground answers in the planner's own notes out of the box.
 */
class KnowledgeRetriever
{
    /** Common words that should not drive relevance. */
    private const STOP = [
        'the', 'and', 'for', 'are', 'but', 'not', 'you', 'your', 'with', 'this', 'that',
        'have', 'has', 'was', 'what', 'when', 'where', 'which', 'how', 'why', 'who', 'can',
        'should', 'would', 'about', 'into', 'from', 'our', 'their', 'them', 'they', 'will',
        'need', 'want', 'does', 'did', 'get', 'got', 'any', 'all', 'is', 'it', 'to', 'of',
        'a', 'an', 'in', 'on', 'do', 'we', 'i', 'me', 'my',
    ];

    /**
     * Retrieve the notes most relevant to a question.
     *
     * @return array<int, array{id:int, title:string, category:?string, scope:string, snippet:string}>
     */
    public function retrieve(User $user, ?int $eventId, string $query, int $limit = 3): array
    {
        $terms = $this->terms($query);
        if (empty($terms)) {
            return [];
        }

        $docs = AiKnowledgeDocument::where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('event_id')->when($eventId, fn ($qq) => $qq->orWhere('event_id', $eventId)))
            ->get(['id', 'event_id', 'title', 'category', 'content', 'pinned']);

        $scored = [];
        foreach ($docs as $doc) {
            $title = Str::lower($doc->title);
            $body = Str::lower($doc->content);
            $score = 0;

            foreach ($terms as $term) {
                $score += 3 * substr_count($title, $term);
                $score += substr_count($body, $term);
            }

            if ($score <= 0) {
                continue;
            }

            if ($doc->pinned) {
                $score += 1;
            }

            $scored[] = [
                'score' => $score,
                'doc' => $doc,
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($row) => [
            'id' => $row['doc']->id,
            'title' => $row['doc']->title,
            'category' => $row['doc']->category,
            'scope' => $row['doc']->event_id ? 'event' : 'global',
            'snippet' => $this->snippet($row['doc']->content, $terms),
        ], array_slice($scored, 0, $limit));
    }

    /**
     * @return array<int, string>
     */
    private function terms(string $query): array
    {
        $words = preg_split('/[^a-z0-9]+/', Str::lower($query)) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn ($w) => strlen($w) > 2 && ! in_array($w, self::STOP, true),
        )));
    }

    /**
     * A focused excerpt: a window around the first matched term, else the head.
     *
     * @param  array<int, string>  $terms
     */
    private function snippet(string $content, array $terms): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $content));
        $lower = Str::lower($clean);

        $pos = null;
        foreach ($terms as $term) {
            $p = strpos($lower, $term);
            if ($p !== false && ($pos === null || $p < $pos)) {
                $pos = $p;
            }
        }

        if ($pos === null || $pos < 120) {
            return Str::limit($clean, 240);
        }

        $start = max(0, $pos - 80);

        return '…' . Str::limit(substr($clean, $start), 240);
    }
}
