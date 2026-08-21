<?php

namespace App\Services\AI\Vendor;

use Illuminate\Support\Str;

/**
 * Turns a vendor's natural-language message into a proposed action, if it reads
 * as a command ("accept the booking request from Sarah", "reply to that review
 * saying thanks"). Only the defined command set is recognised; anything else -
 * and any question - returns null and is answered as a normal chat turn.
 *
 * Rule-based so it behaves identically offline and live: the copilot's ability
 * to *act* never depends on a hosted model.
 */
class VendorCommandParser
{
    /**
     * @return array{type:string, params:array<string,mixed>}|null
     */
    public function parse(string $message): ?array
    {
        $text = Str::lower(trim($message));

        if ($this->looksLikeQuestion($text)) {
            return null;
        }

        // Respond to a booking request.
        if ($this->has($text, ['request', 'booking', 'enquir', 'inquir', 'lead'])
            && $this->has($text, ['accept', 'approve', 'confirm', 'take', 'decline', 'reject', 'turn down', 'pass on'])) {
            $decision = $this->has($text, ['decline', 'reject', 'turn down', 'pass on']) ? 'decline' : 'accept';

            return ['type' => 'vendor_respond_request', 'params' => array_filter([
                'decision' => $decision,
                'hint' => $this->requestHint($message),
                'note' => $this->note($message),
            ], fn ($v) => $v !== null && $v !== '')];
        }

        // Reply to a review.
        if ($this->has($text, ['review', 'rating', 'feedback'])
            && $this->has($text, ['reply', 'respond', 'answer', 'thank'])) {
            return ['type' => 'vendor_reply_review', 'params' => array_filter([
                'hint' => $this->reviewHint($message),
                'body' => $this->replyBody($message),
            ], fn ($v) => $v !== null && $v !== '')];
        }

        return null;
    }

    /** The request the vendor is referring to: a quoted phrase or the planner name after "from". */
    private function requestHint(string $message): string
    {
        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\bfrom\s+(.+?)(?=\s+(?:saying|with|and|because|,|\.)|$)/i', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(?:request|booking|enquiry|inquiry|lead)\s+(?:for|about)\s+(.+?)(?=[,.]|$)/i', $message, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /** An optional note to send with the response. */
    private function note(string $message): string
    {
        if (preg_match('/\b(?:saying|with the note|note[:]?|message[:]?)\s+(.+)$/i', $message, $m)) {
            return trim($m[1], " .\"“”");
        }

        return '';
    }

    private function reviewHint(string $message): string
    {
        if (preg_match('/\breview\s+(?:from|by)\s+(.+?)(?=\s+(?:saying|with|,|\.)|$)/i', $message, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /** The reply text for a review. */
    private function replyBody(string $message): string
    {
        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(?:saying|with|that|reply[:]?)\s+(.+)$/i', $message, $m)) {
            return trim($m[1], " .");
        }

        return '';
    }

    private function looksLikeQuestion(string $text): bool
    {
        if (str_contains($text, '?')) {
            return true;
        }

        return (bool) preg_match('/^(what|which|how|when|who|why|should|can|could|do|does|is|are|will)\b/', $text);
    }

    /** True when the text contains at least one of the needles. */
    private function has(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
