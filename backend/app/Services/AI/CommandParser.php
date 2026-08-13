<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

/**
 * Turns a natural-language chat message into a proposed action, if it reads as a
 * command ("send RSVP reminders", "create a wedding for Sarah & John on Dec 12").
 * It only recognises the defined command set the copilot can actually perform;
 * anything else returns null and is answered as a normal conversation turn.
 *
 * Deliberately rule-based so it works identically in offline and live mode — the
 * copilot's ability to *act* never depends on a hosted model being switched on.
 */
class CommandParser
{
    /**
     * @return array{type:string, params:array<string,mixed>, needs_event:bool}|null
     */
    public function parse(string $message): ?array
    {
        $text = Str::lower(trim($message));

        // Only treat clearly imperative messages as commands.
        if (! $this->looksImperative($text)) {
            return null;
        }

        // Add / adjust / delete a specific task or timeline milestone.
        if ($cmd = $this->timelineCommand($text, $message)) {
            return $cmd;
        }

        // Add / adjust / delete a budget line item.
        if ($cmd = $this->budgetCommand($text, $message)) {
            return $cmd;
        }

        // Generate a venue floor plan for the guest count.
        if ($cmd = $this->venueDesignCommand($text, $message)) {
            return $cmd;
        }

        if ($this->matches($text, ['rsvp'], ['remind', 'reminder', 'chase', 'follow up', 'nudge'])
            || $this->matches($text, ['remind'], ['guest', 'rsvp'])) {
            return $this->action('send_rsvp_reminders', [], true);
        }

        if ($this->matches($text, ['invit'], ['send', 'dispatch', 'out'])
            || $this->matches($text, ['invite'], ['guest'])) {
            return $this->action('send_invitations', [], true);
        }

        if ($this->matches($text, ['checklist'])
            || $this->matches($text, ['task'], ['add', 'create', 'planning', 'generate', 'standard'])) {
            return $this->action('create_tasks', [], true);
        }

        if ($this->matches($text, ['event', 'wedding'], ['create', 'new', 'make', 'set up', 'add', 'start', 'plan'])) {
            return $this->action('create_event', $this->eventParams($message), false);
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $params
     * @return array{type:string, params:array<string,mixed>, needs_event:bool}
     */
    private function action(string $type, array $params, bool $needsEvent): array
    {
        return ['type' => $type, 'params' => $params, 'needs_event' => $needsEvent];
    }

    /**
     * Where a run-on field (title, date, client, theme) should stop: the start of
     * the next recognised clause.
     */
    private const STOP = '(?:on|from|to|at|with|for|budget|theme|description|desc|notes?|note|priority|priorit|guests?|guest|expect|expecting|client)';

    /**
     * Pull the full set of event fields out of a "create event" message: title,
     * date, start/end time, priority, client, guest count, budget, theme and
     * description. Everything is best-effort — the executor validates and the
     * approval card echoes back exactly what was understood.
     *
     * @return array<string,mixed>
     */
    private function eventParams(string $message): array
    {
        $params = [];
        $stop = self::STOP;

        // Priority.
        if (preg_match('/\b(low|medium|high|urgent)[-\s]*priority\b/i', $message, $m)
            || preg_match('/\bpriority[:\s]+(low|medium|high|urgent)\b/i', $message, $m)) {
            $params['priority'] = strtolower($m[1]);
        } elseif (preg_match('/\burgent\b/i', $message)) {
            $params['priority'] = 'urgent';
        }

        // Times — "from X to Y", or a lone start/end.
        $time = '([0-9]{1,2}(?::[0-9]{2})?\s*(?:am|pm)?)';
        if (preg_match('/\bfrom\s+'.$time.'\s*(?:to|until|till|[-–—])\s*'.$time.'/i', $message, $m)) {
            $this->putTime($params, 'start_time', $m[1]);
            $this->putTime($params, 'end_time', $m[2]);
        } else {
            if (preg_match('/\b(?:start(?:ing|s)?|begins?|starts? at|at)\s+'.$time.'/i', $message, $m)) {
                $this->putTime($params, 'start_time', $m[1]);
            }
            if (preg_match('/\b(?:end(?:ing|s)?|ends? at|until|till)\s+'.$time.'/i', $message, $m)) {
                $this->putTime($params, 'end_time', $m[1]);
            }
        }

        // Guest count.
        if (preg_match('/\b(\d[\d,]*)\s+guests?\b/i', $message, $m)
            || preg_match('/\b(?:expected guests?|expecting|guest count)[:\s]+(\d[\d,]*)/i', $message, $m)) {
            $params['expected_guests'] = (int) str_replace(',', '', $m[1]);
        }

        // Total budget.
        if (preg_match('/\b(?:total\s+budget|budget)[:\s]*(?:of\s+)?(?:tzs|tsh)?\s*([\d,]+(?:\.\d+)?)/i', $message, $m)
            || preg_match('/\btzs\s*([\d,]+(?:\.\d+)?)/i', $message, $m)) {
            $params['budget_total'] = (float) str_replace(',', '', $m[1]);
        }

        // Theme.
        if (preg_match('/\btheme[:\s]+(.+?)(?=\s+'.$stop.'\b|[,.;]|$)/i', $message, $m)) {
            $params['theme'] = trim($m[1]);
        }

        // Description / notes — runs to the end of the message.
        if (preg_match('/\b(?:description|described as|notes?|about)[:\s]+(.+)$/i', $message, $m)) {
            $params['description'] = trim($m[1], " .\t\n");
        }

        // Client — "for client X" or "client X" (a name or email hint the
        // executor resolves against the planner's client book).
        // Note: no '.' in the stop set here — client hints are often emails.
        if (preg_match('/\bfor\s+client\s+(.+?)(?=\s+'.$stop.'\b|[,;]|$)/i', $message, $m)
            || preg_match('/\bclient[:\s]+(.+?)(?=\s+'.$stop.'\b|[,;]|$)/i', $message, $m)) {
            $params['client'] = trim($m[1], " .\t\n");
        }

        // Date — after "on", up to the next clause.
        if (preg_match('/\bon\s+(.+?)(?=\s+'.$stop.'\b|[,.;]|$)/i', $message, $m)) {
            $params['event_date'] = trim($m[1]);
        }

        // Title — a quoted phrase, or after called/named/titled, or "for <couple>"
        // (but not "for client …", which is handled above).
        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            $params['title'] = trim($m[1]);
        } elseif (preg_match('/\b(?:called|named|titled)\s+(.+?)(?=\s+'.$stop.'\b|[,.;]|$)/i', $message, $m)) {
            $params['title'] = trim($m[1]);
        } elseif (preg_match('/\bfor\s+(?!client\b)(.+?)(?=\s+'.$stop.'\b|[,.;]|$)/i', $message, $m)) {
            $params['title'] = trim($m[1]);
        }

        return array_filter($params, fn ($v) => $v !== '' && $v !== null && $v !== []);
    }

    /** Normalise a captured time to 24h H:i and store it when valid. */
    private function putTime(array &$params, string $key, string $raw): void
    {
        $raw = strtolower(trim($raw));
        if (! preg_match('/^(\d{1,2})(?::(\d{2}))?\s*(am|pm)?$/', $raw, $m)) {
            return;
        }

        $h = (int) $m[1];
        $min = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
        $ap = $m[3] ?? '';

        if ($ap === 'pm' && $h < 12) { $h += 12; }
        if ($ap === 'am' && $h === 12) { $h = 0; }

        if ($h > 23 || $min > 59) {
            return;
        }

        $params[$key] = sprintf('%02d:%02d', $h, $min);
    }

    private function looksImperative(string $text): bool
    {
        return $this->matches($text, ['send', 'remind', 'reminder', 'chase', 'follow up', 'nudge',
            'invite', 'create', 'new', 'make', 'set up', 'add', 'start', 'plan', 'generate', 'checklist',
            'delete', 'remove', 'drop', 'mark', 'complete', 'done', 'finish', 'rename', 'reschedul',
            'move', 'change', 'update', 'adjust', 'push', 'postpon', 'reopen', 'get rid', 'set',
            'design', 'build', 'increase', 'decrease', 'revise', 'raise', 'lower']);
    }

    /**
     * Detect add/update/delete for a budget line item.
     *
     * @return array{type:string, params:array<string,mixed>, needs_event:bool}|null
     */
    private function budgetCommand(string $text, string $message): ?array
    {
        if (! str_contains($text, 'budget')) {
            return null;
        }

        if ($this->matches($text, ['delete', 'remove', 'drop', 'get rid'])) {
            $name = $this->budgetName($message);

            return $name !== '' ? $this->action('delete_budget_item', ['name' => $name], true) : null;
        }

        $amount = $this->parseAmount($message);
        $category = null;
        if (preg_match('/\bcategory\s+(.+?)(?=\s+(?:for|of|at|to|status|paid|planned|committed)\b|[,;]|$)/i', $message, $m)) {
            $category = trim($m[1]);
        }
        $status = null;
        if (preg_match('/\b(?:as\s+)?(paid|planned|committed|settled|approved|confirmed)\b/i', $message, $m)) {
            $status = strtolower($m[1]);
        }

        $isUpdate = $this->matches($text, ['update', 'change', 'set', 'adjust', 'mark', 'rename',
            'increase', 'decrease', 'raise', 'lower', 'revise']);
        $isAdd = $this->matches($text, ['add', 'create', 'new']);

        if ($isUpdate && ! $isAdd) {
            $name = $this->budgetName($message);
            if ($name === '') {
                return null;
            }
            $p = ['name' => $name];
            if ($amount !== null) { $p['estimated_cost'] = $amount; }
            if ($category) { $p['category'] = $category; }
            if ($status) { $p['status'] = $status; }
            if (preg_match('/\brename\b[^\n]*?\bto\s+(.+?)(?=[,;]|$)/i', $message, $m)) { $p['new_description'] = trim($m[1]); }

            return $this->action('update_budget_item', $p, true);
        }

        if ($isAdd) {
            $desc = $this->budgetDesc($message);
            // Require a real budget-line description (via "budget item X" or
            // "add X to the budget") so we don't hijack sentences that merely
            // mention a budget, e.g. "create a wedding … budget 10,000,000".
            if ($desc === '' || $amount === null) {
                return null;
            }
            $p = array_filter(
                ['description' => $desc, 'estimated_cost' => $amount, 'category' => $category, 'status' => $status],
                fn ($v) => $v !== null && $v !== '',
            );

            return $this->action('add_budget_item', $p, true);
        }

        return null;
    }

    /**
     * Detect a "design the venue / generate a floor plan for the guests" command.
     *
     * @return array{type:string, params:array<string,mixed>, needs_event:bool}|null
     */
    private function venueDesignCommand(string $text, string $message): ?array
    {
        $subject = $this->matches($text, ['venue', 'floor plan', 'floorplan', 'seating plan', 'seating layout', 'layout']);
        $verb = $this->matches($text, ['design', 'create', 'generate', 'make', 'build', 'plan', 'set up', 'arrange']);
        if (! $subject || ! $verb) {
            return null;
        }
        // Don't hijack "create an event" or plain "layout" without a venue cue.
        if (! str_contains($text, 'venue') && ! str_contains($text, 'floor') && ! str_contains($text, 'seating') && ! str_contains($text, 'table')) {
            return null;
        }

        $params = [];
        if (preg_match('/\b(\d[\d,]*)\s+guests?\b/i', $message, $m)) {
            $params['guests'] = (int) str_replace(',', '', $m[1]);
        }
        if (preg_match('/\btables?\s+of\s+(\d+)\b/i', $message, $m)
            || preg_match('/\b(\d+)\s*(?:seats?|people|pax)\s+per\s+table\b/i', $message, $m)) {
            $params['seats_per_table'] = (int) $m[1];
        }

        return $this->action('design_venue', $params, true);
    }

    /** The description hint for updating/deleting a budget item. */
    private function budgetName(string $message): string
    {
        $stop = '(?:\s+(?:to|for|of|at|as|category|status|paid|planned|committed|budget)\b|[,;]|$)';

        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\bthe\s+(.+?)\s+budget\b/i', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\bbudget\s+(?:item|line)\s+(?:called|for|:|named)?\s*(.+?)'.$stop.'/i', $message, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /** The description for a new budget item. */
    private function budgetDesc(string $message): string
    {
        $stop = '(?:\s+(?:for|of|at|costing|worth|priced|category|status|to)\b|[,;]|$)';

        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\bbudget\s+(?:item|line)\s+(?:called|for|:|named)?\s*(.+?)'.$stop.'/i', $message, $m)) {
            $v = trim($m[1]);
            if ($v !== '') {
                return $v;
            }
        }
        if (preg_match('/\badd\s+(?:a\s+|an\s+)?(.+?)\s+to\s+the\s+budget\b/i', $message, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /** Pull a money amount out of the message. */
    private function parseAmount(string $message): ?float
    {
        if (preg_match('/(?:for|of|at|to|costing|worth|priced\s+at|:)\s*(?:tzs|tsh)?\s*([\d,]+(?:\.\d+)?)/i', $message, $m)
            || preg_match('/\btzs\s*([\d,]+(?:\.\d+)?)/i', $message, $m)
            || preg_match('/\b([\d,]{4,}(?:\.\d+)?)\b/', $message, $m)) {
            return (float) str_replace(',', '', $m[1]);
        }

        return null;
    }

    /**
     * Detect an add/update/delete command aimed at a single task or timeline
     * milestone, and pull out the target name plus any changes.
     *
     * @return array{type:string, params:array<string,mixed>, needs_event:bool}|null
     */
    private function timelineCommand(string $text, string $message): ?array
    {
        $isMilestone = str_contains($text, 'milestone') || str_contains($text, 'timeline');
        $isTask = str_contains($text, 'task');
        if (! $isMilestone && ! $isTask) {
            return null;
        }

        // The bulk "planning checklist" is a separate action.
        if (str_contains($text, 'checklist')) {
            return null;
        }

        $kind = $isMilestone ? 'milestone' : 'task';
        $suffix = '_' . $kind;

        // Delete takes precedence, then update, then add.
        if ($this->matches($text, ['delete', 'remove', 'drop', 'get rid'])) {
            $name = $this->itemName($message, $kind);

            return $name !== '' ? $this->action('delete' . $suffix, ['name' => $name], true) : null;
        }

        $isUpdate = $this->matches($text, ['mark', 'complete', 'done', 'finish', 'rename', 'reschedul',
            'move', 'change', 'set', 'update', 'adjust', 'push', 'postpon', 'reopen']);
        $isAdd = $this->matches($text, ['add', 'create', 'new']);

        if ($isUpdate && ! $isAdd) {
            $name = $this->itemName($message, $kind);
            $params = array_merge(['name' => $name], $this->itemChanges($message, $kind, true));

            return $name !== '' ? $this->action('update' . $suffix, $params, true) : null;
        }

        if ($isAdd) {
            $name = $this->itemName($message, $kind);
            $params = array_merge(['title' => $name], $this->itemChanges($message, $kind, false));

            return $name !== '' ? $this->action('add' . $suffix, $params, true) : null;
        }

        return null;
    }

    /** Extract the task/milestone name being referenced. */
    private function itemName(string $message, string $kind): string
    {
        $noun = $kind === 'milestone' ? '(?:timeline milestone|milestone|timeline)' : 'task';
        $stop = '(?:\s+(?:to|as|due|by|on|with|priority|status|deadline|and)\b|[,.;]|$)';

        if (preg_match('/["“](.+?)["”]/u', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b'.$noun.'\s+(?:called|named|titled)\s+(.+?)'.$stop.'/i', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\bthe\s+(.+?)\s+'.$noun.'\b/i', $message, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b'.$noun.'\s+(.+?)'.$stop.'/i', $message, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * Extract the changes for an add/update: due date, priority (tasks), status
     * word and rename target (updates only).
     *
     * @return array<string,mixed>
     */
    private function itemChanges(string $message, string $kind, bool $isUpdate): array
    {
        $c = [];

        if ($kind === 'task'
            && (preg_match('/\b(low|medium|high|urgent)[-\s]*priority\b/i', $message, $m)
                || preg_match('/\bpriority[\s:=]+(?:to\s+)?(low|medium|high|urgent)\b/i', $message, $m))) {
            $c['priority'] = strtolower($m[1]);
        }

        if ($isUpdate && preg_match('/\brename\b[^\n]*?\bto\s+(.+?)(?=[,.;]|$)/i', $message, $m)) {
            $c['new_title'] = trim($m[1]);
        }

        // Due date — "due/by/deadline X", or "reschedule/move/push … to X".
        if (preg_match('/\b(?:due(?:\s+date)?|by|deadline)\s+(?:on\s+)?(.+?)(?=[,.;]|\bpriority\b|\bwith\b|\bas\b|$)/i', $message, $m)) {
            $c['due_date'] = trim($m[1]);
        } elseif (empty($c['new_title'])
            && preg_match('/\b(?:reschedul\w*|move\w*|push\w*|postpon\w*)\b[^\n]*?\bto\s+(.+?)(?=[,.;]|$)/i', $message, $m)) {
            $c['due_date'] = trim($m[1]);
        }

        if ($isUpdate) {
            if (preg_match('/\bas\s+([a-z\s]+?)(?=[,.;]|$)/i', $message, $m)) {
                $c['status'] = trim($m[1]);
            } elseif (preg_match('/\b(completed?|done|finish(?:ed)?|in\s+progress|started|start|ongoing|cancel(?:led)?|reopen|not\s+started|pending|waiting(?:\s+approval)?)\b/i', $message, $m)) {
                $c['status'] = $m[1];
            }
        }

        return array_filter($c, fn ($v) => $v !== '' && $v !== null);
    }

    /**
     * True when the text contains at least one term from every group.
     *
     * @param  array<int,string>  ...$groups
     */
    private function matches(string $text, array ...$groups): bool
    {
        foreach ($groups as $group) {
            $hit = false;
            foreach ($group as $term) {
                if (str_contains($text, $term)) { $hit = true; break; }
            }
            if (! $hit) {
                return false;
            }
        }

        return true;
    }
}
