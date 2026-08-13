<?php

namespace App\Services\AI;

use App\Models\AiMeeting;
use App\Models\User;

/**
 * Turns a captured meeting into a grounded summary and structured action items.
 * Follows the same provider-driver philosophy as the rest of the AI engine: the
 * offline driver analyses the notes deterministically via LocalMeetingAnalyzer;
 * a live driver is handed the notes plus the event context and asked to return
 * the summary and an ACTIONS_JSON block. Either way the output is tagged so it
 * stays distinguishable and auditable.
 */
class MeetingProcessor
{
    public function __construct(
        private readonly AiManager $ai,
        private readonly EventContextBuilder $contextBuilder,
        private readonly LocalMeetingAnalyzer $analyzer,
    ) {}

    /**
     * @return array{summary: string, action_items: array<int, array{description: string, owner: string|null, due_date: null}>, model: string, grounded: bool}
     */
    public function process(User $user, AiMeeting $meeting): array
    {
        $context = $this->buildContext($user, $meeting);
        $grounded = ! empty($context['event']);
        $date = $meeting->meeting_date?->toFormattedDateString();
        $attendees = $meeting->attendees ?? [];

        if ($this->ai->isLive()) {
            $result = $this->ai->provider()->chat(
                $this->systemPrompt($user),
                [['role' => 'user', 'content' => $this->instruction($meeting, $attendees)]],
                $context,
            );
            [$summary, $actions] = $this->splitLiveResult($result['content']);
            $model = $result['model'];
        } else {
            $analysis = $this->analyzer->analyze($context, $meeting->title, $meeting->meeting_type, $date, $attendees, $meeting->notes);
            $summary = $analysis['summary'];
            $actions = $analysis['action_items'];
            $model = 'local-analyzer';
        }

        return [
            'summary' => $summary,
            'action_items' => $actions,
            'model' => $model,
            'grounded' => $grounded,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(User $user, AiMeeting $meeting): array
    {
        if (! $meeting->event) {
            return [];
        }

        return $this->contextBuilder->forEvent($user, $meeting->event) ?: [];
    }

    private function systemPrompt(User $user): string
    {
        $name = config('ai.assistant_name', 'OSEP AI');

        return "You are {$name}, an event-planning copilot helping {$user->full_name} process meeting notes.\n\n"
            . "Rules:\n"
            . "- Ground names, figures and dates in the GROUNDING DATA where relevant; never invent them.\n"
            . "- Write a clean Markdown summary: attendees, key discussion points, decisions.\n"
            . "- Then, on its own final line, output `ACTIONS_JSON:` immediately followed by a JSON array of "
            . 'objects {"description": string, "owner": string|null, "due_date": null}. One object per action item.';
    }

    /**
     * @param  array<int, string>  $attendees
     */
    private function instruction(AiMeeting $meeting, array $attendees): string
    {
        $lines = [
            "Meeting: {$meeting->title} ({$meeting->meeting_type}).",
            $meeting->meeting_date ? 'Date: ' . $meeting->meeting_date->toFormattedDateString() . '.' : null,
            ! empty($attendees) ? 'Attendees: ' . implode(', ', $attendees) . '.' : null,
            "\nRaw notes:\n" . $meeting->notes,
            "\nSummarise the meeting and extract the action items as instructed.",
        ];

        return implode("\n", array_filter($lines));
    }

    /**
     * Split a live reply into the Markdown summary and the parsed action items.
     *
     * @return array{0: string, 1: array<int, array{description: string, owner: string|null, due_date: null}>}
     */
    private function splitLiveResult(string $content): array
    {
        $parts = preg_split('/ACTIONS_JSON:\s*/i', trim($content), 2);
        $summary = trim($parts[0]);
        $actions = [];

        if (isset($parts[1])) {
            $decoded = json_decode(trim($parts[1]), true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (! is_array($row) || empty($row['description'])) {
                        continue;
                    }
                    $actions[] = [
                        'description' => (string) $row['description'],
                        'owner' => isset($row['owner']) && $row['owner'] !== '' ? (string) $row['owner'] : null,
                        'due_date' => null,
                    ];
                }
            }
        }

        return [$summary, $actions];
    }
}
