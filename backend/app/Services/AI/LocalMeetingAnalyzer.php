<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

/**
 * The offline meeting engine - the counterpart to LocalProvider / LocalDocument-
 * Composer. It turns raw meeting notes into a structured Markdown summary and a
 * list of action items deterministically (no external model), grounding the
 * header in the event context when one is supplied. Extraction is heuristic:
 * explicit cues ("Action:", "TODO", "@name") first, then verb-led lines.
 */
class LocalMeetingAnalyzer
{
    /** Lines that clearly flag an action item. */
    private const ACTION_CUES = '/^\s*(action|todo|to-?do|follow[\s-]?up|next\s?step|task)\b\s*[:\-–]?\s*/i';

    /**
     * Label-style prefixes safe to strip from the description ("Action: ...",
     * "TODO - ..."). Requires a separator, so conversational openers like
     * "Follow up with the DJ" keep their wording intact.
     */
    private const STRIP_CUES = '/^\s*(action items?|action|todo|to-?do|task)\s*[:\-–]\s*/i';

    /** Verbs that, when a line leads with them, make it an action. */
    private const ACTION_VERBS = 'send|prepare|confirm|book|follow up|chase|schedule|draft|review|finalise|finalize|share|email|call|order|sign|approve|assign|update|check|arrange|collect|pay|invoice|deliver';

    /** Phrases that mark a decision. */
    private const DECISION_CUES = '/\b(decided|agreed|approved|confirmed that|signed off|we will go with|chose|selected|locked in)\b/i';

    /**
     * @param  array<string, mixed>  $context     event grounding (may be empty)
     * @param  array<int, string>    $attendees
     * @return array{summary: string, action_items: array<int, array{description: string, owner: string|null, due_date: null}>}
     */
    public function analyze(array $context, string $title, string $type, ?string $date, array $attendees, string $notes): array
    {
        $lines = $this->cleanLines($notes);

        $actions = [];
        $decisions = [];
        $points = [];

        foreach ($lines as $line) {
            if ($this->isAction($line)) {
                $actions[] = $this->parseAction($line, $attendees);
            } elseif (preg_match(self::DECISION_CUES, $line)) {
                $decisions[] = $line;
            } else {
                $points[] = $line;
            }
        }

        return [
            'summary' => $this->summary($context, $title, $type, $date, $attendees, $points, $decisions, count($actions)),
            'action_items' => $actions,
        ];
    }

    // -----------------------------------------------------------------

    /**
     * @return array<int, string>
     */
    private function cleanLines(string $notes): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $notes) as $raw) {
            // Strip leading bullets / numbering.
            $line = trim(preg_replace('/^\s*(?:[-*•·]|\d+[.)])\s*/u', '', $raw));
            if ($line !== '' && Str::length($line) > 2) {
                $out[] = $line;
            }
        }

        return $out;
    }

    private function isAction(string $line): bool
    {
        if (preg_match(self::ACTION_CUES, $line)) {
            return true;
        }

        // "<Name> to/will <verb> ..." or a line that leads with an action verb.
        if (preg_match('/\b(to|will|needs? to|should|must)\s+(' . self::ACTION_VERBS . ')\b/i', $line)) {
            return true;
        }

        return (bool) preg_match('/^(' . self::ACTION_VERBS . ')\b/i', $line);
    }

    /**
     * @param  array<int, string>  $attendees
     * @return array{description: string, owner: string|null, due_date: null}
     */
    private function parseAction(string $line, array $attendees): array
    {
        $desc = trim(preg_replace(self::STRIP_CUES, '', $line));
        $owner = null;

        // @name mention.
        if (preg_match('/@([A-Za-z][\w-]+)/', $desc, $m)) {
            $owner = $m[1];
            $desc = trim(str_replace($m[0], '', $desc));
        }

        // "Name:" prefix.
        if (! $owner && preg_match('/^([A-Z][a-zA-Z]+(?:\s+[A-Z][a-zA-Z]+)?)\s*[:\-]\s*(.+)$/', $desc, $m)) {
            $owner = $m[1];
            $desc = trim($m[2]);
        }

        // An attendee named at the start ("Sarah to send ...").
        if (! $owner) {
            foreach ($attendees as $name) {
                $first = trim((string) Str::of($name)->explode(' ')->first());
                if ($first !== '' && preg_match('/^' . preg_quote($first, '/') . '\b/i', $desc)) {
                    $owner = $first;
                    break;
                }
            }
        }

        return [
            'description' => Str::limit(ucfirst($desc), 480, ''),
            'owner' => $owner,
            'due_date' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, string>    $attendees
     * @param  array<int, string>    $points
     * @param  array<int, string>    $decisions
     */
    private function summary(array $context, string $title, string $type, ?string $date, array $attendees, array $points, array $decisions, int $actionCount): string
    {
        $md = [];
        $md[] = '## Meeting summary';

        $meta = array_filter([ucfirst($type) . ' meeting', $date]);
        $md[] = '**' . $title . '** · ' . implode(' · ', $meta);

        // Ground the header in the real event when we have one.
        if (! empty($context['event']['title'])) {
            $ev = $context['event'];
            $when = isset($ev['days_until']) && $ev['days_until'] !== null
                ? " ({$ev['days_until']} days out)"
                : '';
            $md[] = "> Event: {$ev['title']}" . (! empty($ev['date']) ? " · {$ev['date']}" : '') . $when;
        }

        if (! empty($attendees)) {
            $md[] = '';
            $md[] = '**Attendees:** ' . implode(', ', $attendees);
        }

        if (! empty($points)) {
            $md[] = '';
            $md[] = '### Key discussion points';
            foreach (array_slice($points, 0, 8) as $p) {
                $md[] = '- ' . $p;
            }
        }

        if (! empty($decisions)) {
            $md[] = '';
            $md[] = '### Decisions';
            foreach ($decisions as $d) {
                $md[] = '- ' . $d;
            }
        }

        $md[] = '';
        $md[] = '### Action items';
        $md[] = $actionCount > 0
            ? "**{$actionCount} action item" . ($actionCount === 1 ? '' : 's') . '** captured - review, assign and push them to the task board below.'
            : 'No explicit action items were detected. Add them manually if needed.';

        return implode("\n", $md);
    }
}
