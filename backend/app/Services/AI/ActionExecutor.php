<?php

namespace App\Services\AI;

use App\Enums\BudgetItemStatus;
use App\Enums\EventStatus;
use App\Enums\InvitationChannel;
use App\Enums\InvitationStatus;
use App\Enums\MilestoneStatus;
use App\Enums\RsvpStatus;
use App\Enums\TaskStatus;
use Illuminate\Support\Str;
use App\Models\AiAction;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\InvitationDispatcher;
use App\Services\Sms\AfricasTalkingGateway;
use Illuminate\Support\Carbon;

/**
 * Runs the copilot's actions - the things it does *for* the planner rather than
 * just talks about. Each type knows how to (a) preview itself (what would happen,
 * without touching anything) and (b) execute once the planner approves. Automation
 * rules and the chat command parser both queue actions through here; approval is
 * the single choke point where anything actually fires.
 */
class ActionExecutor
{
    public function __construct(
        private readonly InvitationDispatcher $dispatcher,
        private readonly AfricasTalkingGateway $sms,
    ) {}

    /**
     * The actions the copilot can take, in the order they appear in pickers.
     *
     * @return array<string, array{label:string, description:string, needs_event:bool}>
     */
    public static function catalog(): array
    {
        return [
            'send_rsvp_reminders' => [
                'label' => 'Send RSVP reminders',
                'description' => 'Message every guest still awaiting a response.',
                'needs_event' => true,
            ],
            'send_invitations' => [
                'label' => 'Send invitations',
                'description' => 'Invite guests who have not been invited yet.',
                'needs_event' => true,
            ],
            'create_tasks' => [
                'label' => 'Add the planning checklist',
                'description' => 'Create the standard wedding task checklist.',
                'needs_event' => true,
            ],
            'create_event' => [
                'label' => 'Create an event',
                'description' => 'Spin up a new wedding event.',
                'needs_event' => false,
            ],
            'add_task' => ['label' => 'Add a task', 'description' => 'Add a task to the event.', 'needs_event' => true],
            'update_task' => ['label' => 'Update a task', 'description' => 'Adjust an existing task.', 'needs_event' => true],
            'delete_task' => ['label' => 'Delete a task', 'description' => 'Remove a task from the event.', 'needs_event' => true],
            'add_milestone' => ['label' => 'Add a timeline milestone', 'description' => 'Add a milestone to the timeline.', 'needs_event' => true],
            'update_milestone' => ['label' => 'Update a milestone', 'description' => 'Adjust a timeline milestone.', 'needs_event' => true],
            'delete_milestone' => ['label' => 'Delete a milestone', 'description' => 'Remove a timeline milestone.', 'needs_event' => true],
            'add_budget_item' => ['label' => 'Add a budget item', 'description' => 'Add a line to the event budget.', 'needs_event' => true],
            'update_budget_item' => ['label' => 'Update a budget item', 'description' => 'Adjust a budget line.', 'needs_event' => true],
            'delete_budget_item' => ['label' => 'Delete a budget item', 'description' => 'Remove a budget line.', 'needs_event' => true],
            'design_venue' => ['label' => 'Design the venue', 'description' => 'Generate a floor plan sized to the guest count.', 'needs_event' => true],
        ];
    }

    public static function isKnown(string $type): bool
    {
        return array_key_exists($type, self::catalog());
    }

    public static function label(string $type): string
    {
        return self::catalog()[$type]['label'] ?? $type;
    }

    /**
     * Queue an action for approval. Builds the human-readable title/summary from a
     * dry-run preview so the planner sees exactly what they're approving.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $attributes  Extra columns (source, event_id, ai_conversation_id, ai_message_id).
     */
    public function queue(User $user, string $type, array $params, array $attributes = []): AiAction
    {
        $event = $this->resolveEvent($user, $params['event_id'] ?? ($attributes['event_id'] ?? null));
        $preview = $this->preview($user, $type, $params, $event);

        return AiAction::create(array_merge([
            'user_id' => $user->id,
            'event_id' => $event?->id,
            'source' => 'chat',
            'type' => $type,
            'title' => $preview['title'],
            'summary' => $preview['summary'],
            'params' => $params,
            'status' => AiAction::STATUS_PENDING,
        ], $attributes, ['event_id' => $event?->id]));
    }

    /**
     * Describe what an action would do, without doing it.
     *
     * @param  array<string, mixed>  $params
     * @return array{title:string, summary:string, count:int}
     */
    public function preview(User $user, string $type, array $params, ?Event $event = null): array
    {
        $event ??= $this->resolveEvent($user, $params['event_id'] ?? null);

        return match ($type) {
            'send_rsvp_reminders' => $this->previewReminders($event),
            'send_invitations' => $this->previewInvitations($event),
            'create_tasks' => $this->previewTasks($event),
            'create_event' => $this->previewCreateEvent($user, $params),
            'add_task', 'add_milestone' => $this->previewAddItem($event, $type, $params),
            'update_task', 'update_milestone' => $this->previewUpdateItem($event, $type, $params),
            'delete_task', 'delete_milestone' => $this->previewDeleteItem($event, $type, $params),
            'add_budget_item' => $this->previewAddBudget($event, $params),
            'update_budget_item' => $this->previewUpdateBudget($event, $params),
            'delete_budget_item' => $this->previewDeleteBudget($event, $params),
            'design_venue' => $this->previewDesignVenue($event, $params),
            default => ['title' => self::label($type), 'summary' => 'Unknown action.', 'count' => 0],
        };
    }

    /**
     * Perform a pending action and stamp the outcome onto it.
     */
    public function execute(AiAction $action): AiAction
    {
        $user = $action->user()->first();
        $event = $action->event_id ? $this->resolveEvent($user, $action->event_id) : null;
        $params = $action->params ?? [];

        try {
            $result = match ($action->type) {
                'send_rsvp_reminders' => $this->runReminders($user, $event),
                'send_invitations' => $this->runInvitations($user, $event),
                'create_tasks' => $this->runTasks($event),
                'create_event' => $this->runCreateEvent($user, $params),
                'add_task', 'add_milestone' => $this->runAddItem($event, $action->type, $params),
                'update_task', 'update_milestone' => $this->runUpdateItem($event, $action->type, $params),
                'delete_task', 'delete_milestone' => $this->runDeleteItem($event, $action->type, $params),
                'add_budget_item' => $this->runAddBudget($event, $params),
                'update_budget_item' => $this->runUpdateBudget($event, $params),
                'delete_budget_item' => $this->runDeleteBudget($event, $params),
                'design_venue' => $this->runDesignVenue($event, $params),
                default => throw new \RuntimeException("Unknown action [{$action->type}]."),
            };

            $action->forceFill([
                'status' => AiAction::STATUS_DONE,
                'result' => $result,
                'executed_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            $action->forceFill([
                'status' => AiAction::STATUS_FAILED,
                'error' => $e->getMessage(),
                'executed_at' => now(),
            ])->save();
        }

        return $action;
    }

    // -----------------------------------------------------------------
    // RSVP reminders
    // -----------------------------------------------------------------

    private function previewReminders(?Event $event): array
    {
        $count = $event ? $this->pendingGuests($event)->count() : 0;

        return [
            'title' => 'Send RSVP reminders' . ($event ? " · {$event->title}" : ''),
            'summary' => $count > 0
                ? "Message {$count} guest(s) still awaiting an RSVP."
                : 'No guests are awaiting an RSVP right now.',
            'count' => $count,
        ];
    }

    private function runReminders(User $user, ?Event $event): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $sent = 0; $skipped = 0;

        foreach ($this->pendingGuests($event)->get() as $guest) {
            $channel = $this->channelFor($guest);
            if (! $channel) { $skipped++; continue; }
            $this->dispatcher->send($guest, $channel, null, $user, ['kind' => 'reminder']);
            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'message' => "Sent {$sent} reminder(s)."
            . ($skipped ? " {$skipped} guest(s) had no reachable phone or email." : '')];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Guest>
     */
    private function pendingGuests(Event $event)
    {
        return $event->guests()
            ->whereNull('archived_at')
            ->whereIn('rsvp_status', RsvpStatus::pendingStates());
    }

    // -----------------------------------------------------------------
    // Invitations
    // -----------------------------------------------------------------

    private function previewInvitations(?Event $event): array
    {
        $count = $event ? $this->uninvitedGuests($event)->count() : 0;

        return [
            'title' => 'Send invitations' . ($event ? " · {$event->title}" : ''),
            'summary' => $count > 0
                ? "Invite {$count} guest(s) who haven't been invited yet."
                : 'Every guest has already been invited.',
            'count' => $count,
        ];
    }

    private function runInvitations(User $user, ?Event $event): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $sent = 0; $skipped = 0;

        foreach ($this->uninvitedGuests($event)->get() as $guest) {
            $channel = $this->channelFor($guest);
            if (! $channel) { $skipped++; continue; }
            $this->dispatcher->send($guest, $channel, null, $user, ['kind' => 'invitation']);
            $sent++;
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'message' => "Sent {$sent} invitation(s)."
            . ($skipped ? " {$skipped} guest(s) had no reachable phone or email." : '')];
    }

    /**
     * Guests who have not been sent an invitation yet (never sent, or only a
     * failed attempt).
     *
     * @return \Illuminate\Database\Eloquent\Builder<Guest>
     */
    private function uninvitedGuests(Event $event)
    {
        return $event->guests()
            ->whereNull('archived_at')
            ->where(fn ($q) => $q
                ->whereNull('invitation_status')
                ->orWhereIn('invitation_status', [InvitationStatus::Draft->value, InvitationStatus::Failed->value]));
    }

    // -----------------------------------------------------------------
    // Task checklist
    // -----------------------------------------------------------------

    /**
     * A sensible starter checklist for a wedding, with due dates counted back
     * from the event date.
     *
     * @return array<int, array{title:string, weeks_before:int, priority:string}>
     */
    private static function checklist(): array
    {
        return [
            ['title' => 'Confirm the wedding date & venue', 'weeks_before' => 24, 'priority' => 'high'],
            ['title' => 'Set and agree the budget', 'weeks_before' => 22, 'priority' => 'high'],
            ['title' => 'Draft the guest list', 'weeks_before' => 20, 'priority' => 'medium'],
            ['title' => 'Book key vendors (catering, photography)', 'weeks_before' => 16, 'priority' => 'high'],
            ['title' => 'Send invitations', 'weeks_before' => 8, 'priority' => 'medium'],
            ['title' => 'Finalise the menu & catering numbers', 'weeks_before' => 4, 'priority' => 'medium'],
            ['title' => 'Confirm the run-of-show timeline', 'weeks_before' => 2, 'priority' => 'high'],
            ['title' => 'Chase outstanding RSVPs', 'weeks_before' => 2, 'priority' => 'medium'],
        ];
    }

    private function previewTasks(?Event $event): array
    {
        $new = $event ? $this->newChecklistItems($event) : [];

        return [
            'title' => 'Add the planning checklist' . ($event ? " · {$event->title}" : ''),
            'summary' => count($new) > 0
                ? 'Create ' . count($new) . ' checklist task(s) for this wedding.'
                : 'The checklist tasks already exist on this event.',
            'count' => count($new),
        ];
    }

    private function runTasks(?Event $event): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');

        $items = $this->newChecklistItems($event);
        $base = $event->tasks()->max('position') ?? 0;
        $date = $event->event_date ? Carbon::parse($event->event_date) : null;

        foreach ($items as $i => $item) {
            $event->tasks()->create([
                'title' => $item['title'],
                'priority' => $item['priority'],
                'status' => TaskStatus::NotStarted->value,
                'due_date' => $date ? (clone $date)->subWeeks($item['weeks_before'])->toDateString() : null,
                'position' => $base + $i + 1,
            ]);
        }

        return ['created' => count($items), 'message' => 'Created ' . count($items) . ' checklist task(s).'];
    }

    /**
     * Checklist items not already present on the event (matched by title).
     *
     * @return array<int, array{title:string, weeks_before:int, priority:string}>
     */
    private function newChecklistItems(Event $event): array
    {
        $existing = $event->tasks()->pluck('title')->map(fn ($t) => mb_strtolower($t))->all();

        return array_values(array_filter(
            self::checklist(),
            fn ($item) => ! in_array(mb_strtolower($item['title']), $existing, true),
        ));
    }

    // -----------------------------------------------------------------
    // Create event
    // -----------------------------------------------------------------

    private function previewCreateEvent(User $user, array $params): array
    {
        $title = trim((string) ($params['title'] ?? '')) ?: 'New wedding';
        $date = $this->parseDate($params['event_date'] ?? null);

        $bits = [];
        if ($date) { $bits[] = $date->format('M j, Y'); }
        if (! empty($params['start_time'])) {
            $bits[] = 'from ' . $params['start_time'] . (! empty($params['end_time']) ? ' to ' . $params['end_time'] : '');
        } elseif (! empty($params['end_time'])) {
            $bits[] = 'until ' . $params['end_time'];
        }
        if (! empty($params['priority'])) { $bits[] = ucfirst($params['priority']) . ' priority'; }

        $client = $this->resolveClient($user, $params);
        if ($client) {
            $bits[] = 'client ' . $client->full_name;
        } elseif (! empty($params['client'])) {
            $bits[] = "client \"{$params['client']}\" (not matched - left unset)";
        }

        if (isset($params['expected_guests'])) { $bits[] = number_format((int) $params['expected_guests']) . ' guests'; }
        if (isset($params['budget_total'])) { $bits[] = 'TZS ' . number_format((float) $params['budget_total']); }
        if (! empty($params['theme'])) { $bits[] = 'theme: ' . $params['theme']; }
        if (! empty($params['description'])) { $bits[] = 'with a description'; }

        $detail = $bits ? ' - ' . implode(' · ', $bits) : '';

        return [
            'title' => "Create event · {$title}",
            'summary' => "Create the wedding \"{$title}\"{$detail}.",
            'count' => 1,
        ];
    }

    private function runCreateEvent(User $user, array $params): array
    {
        $title = trim((string) ($params['title'] ?? '')) ?: 'New wedding';
        $date = $this->parseDate($params['event_date'] ?? null);
        $priority = in_array($params['priority'] ?? null, ['low', 'medium', 'high', 'urgent'], true)
            ? $params['priority'] : 'medium';

        $attributes = [
            'title' => mb_substr($title, 0, 255),
            'event_type' => 'Wedding',
            'event_code' => Event::nextCode(),
            'status' => EventStatus::Planning->value,
            'priority' => $priority,
            'event_date' => $date?->toDateString(),
        ];

        if ($client = $this->resolveClient($user, $params)) {
            $attributes['client_id'] = $client->id;
        }
        if (! empty($params['start_time'])) { $attributes['start_time'] = $params['start_time']; }
        if (! empty($params['end_time'])) { $attributes['end_time'] = $params['end_time']; }
        if (isset($params['expected_guests'])) { $attributes['expected_guests'] = max(0, (int) $params['expected_guests']); }
        if (isset($params['budget_total'])) { $attributes['budget_total'] = max(0, (float) $params['budget_total']); }
        if (! empty($params['theme'])) { $attributes['theme'] = mb_substr((string) $params['theme'], 0, 255); }
        if (! empty($params['description'])) { $attributes['description'] = mb_substr((string) $params['description'], 0, 5000); }

        $event = $user->plannedEvents()->create($attributes);

        return ['event_id' => $event->id, 'message' => "Created the wedding \"{$event->title}\"."];
    }

    /**
     * Resolve a client reference (numeric id or a name/email hint) against the
     * planner's client book - clients already on one of their events. Never
     * matches an arbitrary user.
     */
    private function resolveClient(User $user, array $params): ?User
    {
        $ids = $user->plannedEvents()->whereNotNull('client_id')->pluck('client_id')->unique();
        if ($ids->isEmpty()) {
            return null;
        }

        $book = User::whereIn('id', $ids)->get();

        if (! empty($params['client_id'])) {
            return $book->firstWhere('id', (int) $params['client_id']);
        }

        $hint = mb_strtolower(trim((string) ($params['client'] ?? '')));
        if ($hint === '') {
            return null;
        }

        return $book->first(fn (User $c) => mb_strtolower((string) $c->email) === $hint)
            ?? $book->first(fn (User $c) => str_contains(mb_strtolower($c->full_name), $hint));
    }

    // -----------------------------------------------------------------
    // Timeline & tasks (add / update / delete a single item)
    // -----------------------------------------------------------------

    /**
     * Shape metadata for a task vs a timeline milestone: they share the same
     * add/update/delete flow but differ in table, title column and status set.
     *
     * @return array{kind:string, noun:string, field:string}
     */
    private function itemMeta(string $type): array
    {
        $isTask = str_ends_with($type, '_task');

        return [
            'kind' => $isTask ? 'task' : 'milestone',
            'noun' => $isTask ? 'task' : 'timeline milestone',
            'field' => $isTask ? 'title' : 'name',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<\Illuminate\Database\Eloquent\Model, Event> */
    private function itemsQuery(Event $event, string $kind)
    {
        return $kind === 'task' ? $event->tasks() : $event->milestones();
    }

    /** Find a task/milestone by an exact then fuzzy title match within the event. */
    private function resolveItem(Event $event, array $meta, string $hint)
    {
        $hint = mb_strtolower(trim($hint));
        if ($hint === '') {
            return null;
        }

        $items = $this->itemsQuery($event, $meta['kind'])->get();
        $field = $meta['field'];

        return $items->first(fn ($i) => mb_strtolower((string) $i->{$field}) === $hint)
            ?? $items->first(fn ($i) => str_contains(mb_strtolower((string) $i->{$field}), $hint));
    }

    private function previewAddItem(?Event $event, string $type, array $params): array
    {
        $meta = $this->itemMeta($type);
        $title = trim((string) ($params['title'] ?? ''));
        $date = $this->parseDate($params['due_date'] ?? null);

        $bits = [];
        if ($date) { $bits[] = 'due ' . $date->format('M j, Y'); }
        if ($meta['kind'] === 'task' && ! empty($params['priority'])) { $bits[] = ucfirst($params['priority']) . ' priority'; }
        $detail = $bits ? ' (' . implode(' · ', $bits) . ')' : '';

        return [
            'title' => 'Add ' . $meta['noun'] . ($event ? " · {$event->title}" : ''),
            'summary' => $title !== ''
                ? "Add the {$meta['noun']} \"{$title}\"{$detail}."
                : "No {$meta['noun']} name was given.",
            'count' => $title !== '' ? 1 : 0,
        ];
    }

    private function runAddItem(?Event $event, string $type, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $meta = $this->itemMeta($type);
        $title = trim((string) ($params['title'] ?? ''));
        abort_if($title === '', 422, "A {$meta['noun']} name is required.");

        $date = $this->parseDate($params['due_date'] ?? null);
        $rel = $this->itemsQuery($event, $meta['kind']);

        $attributes = [
            $meta['field'] => mb_substr($title, 0, 255),
            'due_date' => $date?->toDateString(),
            'position' => (int) $rel->max('position') + 1,
        ];

        if ($meta['kind'] === 'task') {
            $attributes['status'] = TaskStatus::NotStarted->value;
            $attributes['priority'] = in_array($params['priority'] ?? null, ['low', 'medium', 'high', 'urgent'], true)
                ? $params['priority'] : 'medium';
        } else {
            $attributes['status'] = MilestoneStatus::Pending->value;
        }

        $item = $rel->create($attributes);

        return ['id' => $item->id, 'message' => "Added the {$meta['noun']} \"{$title}\"."];
    }

    private function previewUpdateItem(?Event $event, string $type, array $params): array
    {
        $meta = $this->itemMeta($type);
        $hint = (string) ($params['name'] ?? '');
        $item = $event ? $this->resolveItem($event, $meta, $hint) : null;

        if (! $item) {
            return [
                'title' => 'Update ' . $meta['noun'],
                'summary' => $hint !== ''
                    ? "No {$meta['noun']} matching \"{$hint}\" was found."
                    : "Name the {$meta['noun']} you want to update.",
                'count' => 0,
            ];
        }

        $changes = $this->describeChanges($meta, $params);

        return [
            'title' => 'Update ' . $meta['noun'] . ' · ' . $item->{$meta['field']},
            'summary' => "Update \"{$item->{$meta['field']}}\"" . ($changes ? ': ' . $changes : ' - nothing to change') . '.',
            'count' => $changes ? 1 : 0,
        ];
    }

    private function runUpdateItem(?Event $event, string $type, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $meta = $this->itemMeta($type);
        $item = $this->resolveItem($event, $meta, (string) ($params['name'] ?? ''));
        abort_if($item === null, 422, "No {$meta['noun']} matching \"" . ($params['name'] ?? '') . "\" was found.");

        $updates = $this->computeUpdates($meta, $params);
        abort_if($updates === [], 422, 'No changes were understood.');

        $item->fill($updates)->save();

        return ['id' => $item->id, 'message' => "Updated the {$meta['noun']} \"{$item->{$meta['field']}}\"."];
    }

    private function previewDeleteItem(?Event $event, string $type, array $params): array
    {
        $meta = $this->itemMeta($type);
        $hint = (string) ($params['name'] ?? '');
        $item = $event ? $this->resolveItem($event, $meta, $hint) : null;

        return [
            'title' => 'Delete ' . $meta['noun'] . ($item ? ' · ' . $item->{$meta['field']} : ''),
            'summary' => $item
                ? "Permanently delete the {$meta['noun']} \"{$item->{$meta['field']}}\"."
                : ($hint !== '' ? "No {$meta['noun']} matching \"{$hint}\" was found." : "Name the {$meta['noun']} to delete."),
            'count' => $item ? 1 : 0,
        ];
    }

    private function runDeleteItem(?Event $event, string $type, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $meta = $this->itemMeta($type);
        $item = $this->resolveItem($event, $meta, (string) ($params['name'] ?? ''));
        abort_if($item === null, 422, "No {$meta['noun']} matching \"" . ($params['name'] ?? '') . "\" was found.");

        $name = $item->{$meta['field']};
        $item->delete();

        return ['message' => "Deleted the {$meta['noun']} \"{$name}\"."];
    }

    /**
     * Build the update payload from parsed change params (status word, due date,
     * priority, new title).
     *
     * @return array<string, mixed>
     */
    private function computeUpdates(array $meta, array $params): array
    {
        $updates = [];

        if (! empty($params['status']) && ($status = $this->normalizeStatus($meta['kind'], (string) $params['status']))) {
            $updates['status'] = $status;
            if ($meta['kind'] === 'task') {
                $updates['completed_at'] = $status === TaskStatus::Completed->value ? now() : null;
            }
        }

        if (! empty($params['due_date']) && ($date = $this->parseDate($params['due_date']))) {
            $updates['due_date'] = $date->toDateString();
        }

        if ($meta['kind'] === 'task' && ! empty($params['priority'])
            && in_array($params['priority'], ['low', 'medium', 'high', 'urgent'], true)) {
            $updates['priority'] = $params['priority'];
        }

        if (! empty($params['new_title'])) {
            $updates[$meta['field']] = mb_substr((string) $params['new_title'], 0, 255);
        }

        return $updates;
    }

    /** Human summary of the changes an update would make. */
    private function describeChanges(array $meta, array $params): string
    {
        $updates = $this->computeUpdates($meta, $params);
        $bits = [];

        if (isset($updates['status'])) { $bits[] = 'status → ' . str_replace('_', ' ', $updates['status']); }
        if (isset($updates['due_date'])) { $bits[] = 'due → ' . $updates['due_date']; }
        if (isset($updates['priority'])) { $bits[] = 'priority → ' . $updates['priority']; }
        if (isset($updates[$meta['field']])) { $bits[] = 'renamed to "' . $updates[$meta['field']] . '"'; }

        return implode(', ', $bits);
    }

    /** Map a free-text status word onto a valid status for the kind. */
    private function normalizeStatus(string $kind, string $word): ?string
    {
        $w = mb_strtolower($word);
        $has = fn (string ...$needles) => array_filter($needles, fn ($n) => str_contains($w, $n)) !== [];

        return match (true) {
            $has('complete', 'done', 'finish') => $kind === 'task' ? TaskStatus::Completed->value : MilestoneStatus::Completed->value,
            $has('waiting', 'approval', 'review') => $kind === 'task' ? TaskStatus::WaitingApproval->value : MilestoneStatus::WaitingApproval->value,
            $has('progress', 'started', 'start', 'doing', 'ongoing') => $kind === 'task' ? TaskStatus::InProgress->value : MilestoneStatus::InProgress->value,
            $kind === 'task' && $has('cancel') => TaskStatus::Cancelled->value,
            $has('not started', 'todo', 'to do', 'reset', 'reopen', 'pending') => $kind === 'task' ? TaskStatus::NotStarted->value : MilestoneStatus::Pending->value,
            default => null,
        };
    }

    // -----------------------------------------------------------------
    // Budget line items (add / update / delete)
    // -----------------------------------------------------------------

    private function previewAddBudget(?Event $event, array $params): array
    {
        $desc = trim((string) ($params['description'] ?? ''));
        $cost = $params['estimated_cost'] ?? null;

        return [
            'title' => 'Add budget item' . ($event ? " · {$event->title}" : ''),
            'summary' => $desc !== '' && $cost !== null
                ? "Add \"{$desc}\" (" . ($params['category'] ?? 'General') . ') at TZS ' . number_format((float) $cost) . '.'
                : 'A description and an estimated cost are required.',
            'count' => ($desc !== '' && $cost !== null) ? 1 : 0,
        ];
    }

    private function runAddBudget(?Event $event, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $desc = trim((string) ($params['description'] ?? ''));
        $cost = $params['estimated_cost'] ?? null;
        abort_if($desc === '' || $cost === null, 422, 'A description and an estimated cost are required.');

        $attributes = [
            'category' => mb_substr(trim((string) ($params['category'] ?? 'General')) ?: 'General', 0, 100),
            'description' => mb_substr($desc, 0, 255),
            'estimated_cost' => max(0, (float) $cost),
            'status' => $this->budgetStatus($params['status'] ?? null) ?? BudgetItemStatus::Planned->value,
        ];
        if (isset($params['actual_cost'])) {
            $attributes['actual_cost'] = max(0, (float) $params['actual_cost']);
        }

        $item = $event->budgetItems()->create($attributes);

        return ['id' => $item->id, 'message' => "Added the budget item \"{$item->description}\"."];
    }

    private function previewUpdateBudget(?Event $event, array $params): array
    {
        $item = $event ? $this->resolveBudgetItem($event, (string) ($params['name'] ?? '')) : null;
        if (! $item) {
            return ['title' => 'Update budget item', 'summary' => 'No matching budget item was found.', 'count' => 0];
        }

        $changes = $this->describeBudgetChanges($params);

        return [
            'title' => 'Update budget item · ' . $item->description,
            'summary' => "Update \"{$item->description}\"" . ($changes ? ': ' . $changes : ' - nothing to change') . '.',
            'count' => $changes ? 1 : 0,
        ];
    }

    private function runUpdateBudget(?Event $event, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $item = $this->resolveBudgetItem($event, (string) ($params['name'] ?? ''));
        abort_if($item === null, 422, 'No matching budget item was found.');

        $updates = $this->computeBudgetUpdates($params);
        abort_if($updates === [], 422, 'No changes were understood.');

        $item->fill($updates)->save();

        return ['id' => $item->id, 'message' => "Updated the budget item \"{$item->description}\"."];
    }

    private function previewDeleteBudget(?Event $event, array $params): array
    {
        $item = $event ? $this->resolveBudgetItem($event, (string) ($params['name'] ?? '')) : null;

        return [
            'title' => 'Delete budget item' . ($item ? ' · ' . $item->description : ''),
            'summary' => $item
                ? "Permanently delete the budget item \"{$item->description}\"."
                : 'No matching budget item was found.',
            'count' => $item ? 1 : 0,
        ];
    }

    private function runDeleteBudget(?Event $event, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $item = $this->resolveBudgetItem($event, (string) ($params['name'] ?? ''));
        abort_if($item === null, 422, 'No matching budget item was found.');

        $desc = $item->description;
        $item->delete();

        return ['message' => "Deleted the budget item \"{$desc}\"."];
    }

    /** Find a budget line by a fuzzy match on its description (then category). */
    private function resolveBudgetItem(Event $event, string $hint)
    {
        $hint = mb_strtolower(trim($hint));
        if ($hint === '') {
            return null;
        }

        $items = $event->budgetItems()->get();

        return $items->first(fn ($i) => mb_strtolower((string) $i->description) === $hint)
            ?? $items->first(fn ($i) => str_contains(mb_strtolower((string) $i->description), $hint))
            ?? $items->first(fn ($i) => str_contains(mb_strtolower((string) $i->category), $hint));
    }

    /**
     * @return array<string, mixed>
     */
    private function computeBudgetUpdates(array $params): array
    {
        $updates = [];
        if (isset($params['estimated_cost'])) { $updates['estimated_cost'] = max(0, (float) $params['estimated_cost']); }
        if (isset($params['actual_cost'])) { $updates['actual_cost'] = max(0, (float) $params['actual_cost']); }
        if (! empty($params['category'])) { $updates['category'] = mb_substr((string) $params['category'], 0, 100); }
        if (! empty($params['new_description'])) { $updates['description'] = mb_substr((string) $params['new_description'], 0, 255); }
        if (! empty($params['status']) && ($s = $this->budgetStatus($params['status']))) { $updates['status'] = $s; }

        return $updates;
    }

    private function describeBudgetChanges(array $params): string
    {
        $u = $this->computeBudgetUpdates($params);
        $bits = [];
        if (isset($u['estimated_cost'])) { $bits[] = 'estimated → TZS ' . number_format($u['estimated_cost']); }
        if (isset($u['actual_cost'])) { $bits[] = 'actual → TZS ' . number_format($u['actual_cost']); }
        if (isset($u['category'])) { $bits[] = 'category → ' . $u['category']; }
        if (isset($u['status'])) { $bits[] = 'status → ' . $u['status']; }
        if (isset($u['description'])) { $bits[] = 'renamed to "' . $u['description'] . '"'; }

        return implode(', ', $bits);
    }

    private function budgetStatus(?string $word): ?string
    {
        $w = mb_strtolower((string) $word);
        if ($w === '') {
            return null;
        }

        return match (true) {
            str_contains($w, 'paid'), str_contains($w, 'settled') => BudgetItemStatus::Paid->value,
            str_contains($w, 'commit'), str_contains($w, 'approv'), str_contains($w, 'confirm') => BudgetItemStatus::Committed->value,
            str_contains($w, 'plan') => BudgetItemStatus::Planned->value,
            default => null,
        };
    }

    // -----------------------------------------------------------------
    // Venue design (generate a floor plan sized to the guest count)
    // -----------------------------------------------------------------

    private function previewDesignVenue(?Event $event, array $params): array
    {
        $guests = $event ? $this->venueGuestCount($event, $params) : (int) ($params['guests'] ?? 0);
        $perTable = $this->seatsPerTable($params);
        $tables = $guests > 0 ? (int) ceil($guests / $perTable) : 0;

        return [
            'title' => 'Design venue' . ($event ? " · {$event->title}" : ''),
            'summary' => $guests > 0
                ? "Create a new floor plan for {$guests} guest(s): {$tables} round table(s) of {$perTable}, a stage and a dance floor."
                : 'No guest count is available - add guests to the event or say how many.',
            'count' => $tables,
        ];
    }

    private function runDesignVenue(?Event $event, array $params): array
    {
        abort_unless($event !== null, 422, 'This action needs an event.');
        $guests = $this->venueGuestCount($event, $params);
        abort_if($guests < 1, 422, 'No guest count is available for this event.');

        $perTable = $this->seatsPerTable($params);
        $tables = (int) ceil($guests / $perTable);

        $cell = 3.5;            // metres per table cell (table + walking space)
        $cols = max(1, (int) ceil(sqrt($tables)));
        $rows = (int) ceil($tables / $cols);
        $topReserve = 15.0;     // room for the stage + dance floor
        $width = round(max(14, $cols * $cell + 4), 1);
        $height = round($topReserve + $rows * $cell + 2, 1);

        $layout = $event->venueLayouts()->create([
            'created_by' => $event->planner_id,
            'layout_name' => "AI Layout - {$guests} guests",
            'venue_name' => $event->venue ?: 'Main hall',
            'width' => $width,
            'height' => $height,
            'unit' => 'm',
            'max_capacity' => $guests,
            'version' => (int) $event->venueLayouts()->max('version') + 1,
            'meta' => ['generated' => true],
        ]);

        $objects = [
            $this->venueObject('medium_stage', 'Stage', round(($width - 10) / 2, 2), 1, 10, 4, '#7c3aed', 'stage'),
            $this->venueObject('dance_floor_medium', 'Dance Floor', round(($width - 8) / 2, 2), 6, 8, 8, '#c4b5fd', 'decoration'),
        ];

        $tableW = 2.0;
        $startX = round(($width - $cols * $cell) / 2 + ($cell - $tableW) / 2, 2);
        $n = 0;
        for ($r = 0; $r < $rows && $n < $tables; $r++) {
            for ($c = 0; $c < $cols && $n < $tables; $c++) {
                $n++;
                $objects[] = $this->venueObject(
                    'round_table', "Table {$n}",
                    round($startX + $c * $cell, 2), round($topReserve + $r * $cell, 2),
                    $tableW, $tableW, '#ffffff', 'furniture', ['seats' => $perTable],
                );
            }
        }

        foreach ($objects as $o) {
            $layout->objects()->create($o);
        }

        return [
            'layout_id' => $layout->id,
            'tables' => $tables,
            'message' => "Created a venue design with {$tables} table(s) for {$guests} guest(s).",
        ];
    }

    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    private function venueObject(string $type, string $name, float $x, float $y, float $w, float $h, string $color, string $layer, array $props = []): array
    {
        return [
            'uid' => (string) Str::uuid(),
            'object_type' => $type,
            'object_name' => $name,
            'x_position' => $x,
            'y_position' => $y,
            'width' => $w,
            'height' => $h,
            'rotation' => 0,
            'color' => $color,
            'layer' => $layer,
            'properties' => $props ?: null,
        ];
    }

    private function venueGuestCount(Event $event, array $params): int
    {
        if (! empty($params['guests'])) {
            return max(0, (int) $params['guests']);
        }

        $count = $event->guests()->whereNull('archived_at')->count();

        return $count > 0 ? $count : (int) ($event->expected_guests ?? 0);
    }

    private function seatsPerTable(array $params): int
    {
        return max(2, min(20, (int) ($params['seats_per_table'] ?? 10)));
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /** Prefer SMS when the gateway is live and the guest has a phone; else email. */
    private function channelFor(Guest $guest): ?InvitationChannel
    {
        if ($this->sms->configured() && $guest->phone) {
            return InvitationChannel::Sms;
        }

        return $guest->email ? InvitationChannel::Email : null;
    }

    private function resolveEvent(User $user, mixed $eventId): ?Event
    {
        if (! $eventId) {
            return null;
        }

        return Event::where('planner_id', $user->id)->find($eventId);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
