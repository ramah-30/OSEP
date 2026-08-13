<?php

namespace App\Services\AI\Client;

use Illuminate\Support\Str;

/**
 * Turns a client's natural-language message into a proposed action, if it reads
 * as a command ("approve the catering menu", "add Jane Doe to the guest list").
 * Only the defined command set is recognised; anything else — and any question —
 * returns null and is answered as a normal chat turn. Rule-based, so it behaves
 * identically offline and live.
 */
class ClientCommandParser
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

        // Add a guest.
        if ($this->has($text, ['add', 'invite'])
            && $this->has($text, ['guest', 'to the list', 'to my list', 'to the guest'])) {
            $name = $this->guestName($message);

            return $name !== '' ? ['type' => 'client_add_guest', 'params' => array_filter([
                'name' => $name,
                'email' => $this->email($message),
                'phone' => $this->phone($message),
            ], fn ($v) => $v !== null && $v !== '')] : null;
        }

        // Book a planner. "find/recommend a planner" is informational (not a
        // command) — only an explicit book/hire instruction proposes an action.
        if ($this->has($text, ['book ', 'book a', 'hire', 'send a booking request', 'request to book'])
            && ! $this->has($text, ['booked', 'my booking', 'booking request status', 'facebook'])) {
            $name = $this->plannerName($message);

            return $name !== '' ? ['type' => 'client_book_planner', 'params' => array_filter([
                'planner' => $name,
                'message' => $this->note($message),
            ], fn ($v) => $v !== null && $v !== '')] : null;
        }

        // Respond to an approval.
        if ($this->has($text, ['approv', 'reject', 'decline', 'sign off', 'request changes', 'ask for changes'])) {
            $decision = match (true) {
                $this->has($text, ['reject', 'decline']) => 'reject',
                $this->has($text, ['request changes', 'ask for changes', 'changes']) => 'changes',
                default => 'approve',
            };

            return ['type' => 'client_respond_approval', 'params' => array_filter([
                'decision' => $decision,
                'hint' => $this->approvalHint($message),
                'note' => $this->note($message),
            ], fn ($v) => $v !== null && $v !== '')];
        }

        return null;
    }

    /** The guest's name: quoted, or after "add/invite (guest)". */
    private function guestName(string $message): string
    {
        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(?:add|invite)\s+(?:a\s+guest\s+(?:called|named)\s+)?(?:guest\s+)?(.+?)(?=\s+(?:to|with|at|as|,|\.|email|phone)\b|$)/i', $message, $m)) {
            $name = trim($m[1]);
            // Strip a trailing "to the guest list" style tail if it slipped through.
            $name = preg_replace('/\s+to (the|my).*/i', '', $name);

            return trim($name);
        }

        return '';
    }

    /** The planner to book: a quoted name, or the text after "book"/"hire". */
    private function plannerName(string $message): string
    {
        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(?:book|hire)\s+(?:planner\s+)?(.+?)(?=\s+(?:for|to|about|with|because|saying|,|\.)\b|$)/i', $message, $m)) {
            $name = trim($m[1]);
            // Drop a leading article and a trailing "planner" if they slipped in.
            $name = preg_replace('/^(the|a|an)\s+/i', '', $name);
            $name = trim(preg_replace('/\s*\bplanners?\b\s*$/i', '', $name));

            // A generic phrase ("a planner") carries no name — let it fall through
            // to the chat directory instead of proposing a booking.
            if (in_array(mb_strtolower($name), ['', 'planner', 'planners'], true)) {
                return '';
            }

            return $name;
        }

        return '';
    }

    /** The approval being referred to: a quoted phrase or the text after the verb. */
    private function approvalHint(string $message): string
    {
        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(?:approve|reject|decline|sign off on|changes to|changes on)\s+(?:the\s+)?(.+?)(?=\s+(?:saying|with|because|,|\.)|$)/i', $message, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    private function note(string $message): string
    {
        if (preg_match('/\b(?:saying|with the note|note[:]?|because)\s+(.+)$/i', $message, $m)) {
            return trim($m[1], " .\"“”");
        }

        return '';
    }

    private function email(string $message): string
    {
        return preg_match('/([\w.+-]+@[\w-]+\.[\w.-]+)/', $message, $m) ? $m[1] : '';
    }

    private function phone(string $message): string
    {
        return preg_match('/(\+?\d[\d\s-]{7,}\d)/', $message, $m) ? trim($m[1]) : '';
    }

    private function looksLikeQuestion(string $text): bool
    {
        if (str_contains($text, '?')) {
            return true;
        }

        return (bool) preg_match('/^(what|which|how|when|who|why|should|can|could|do|does|is|are|will)\b/', $text);
    }

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
