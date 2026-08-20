<?php

namespace App\Services\AI;

use App\Enums\DocumentStatus;
use App\Enums\RecommendationStatus;
use App\Models\AiAutomationRule;
use App\Models\AiAutomationRun;
use App\Models\AiGeneratedDocument;
use App\Models\AiRecommendation;
use App\Models\AiTemplate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Evaluates the planner's automation rules against live event data and, when a
 * condition is met, has the copilot act — raise a recommendation, draft a
 * document, or simply flag it. A rule fires at most once per event per day (the
 * run log is the dedupe window) so evaluation is safe to trigger on demand.
 */
class AutomationEngine
{
    public function __construct(
        private readonly EventContextBuilder $contextBuilder,
        private readonly DocumentGenerator $generator,
        private readonly DocumentTemplateCatalog $catalog,
        private readonly ActionExecutor $executor,
    ) {}

    /**
     * The supported triggers and how to read them from an event context.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function triggers(): array
    {
        return [
            'budget_over' => ['label' => 'Budget utilisation reaches', 'unit' => '%', 'default' => 90, 'category' => 'budget', 'tab' => 'budget', 'template' => 'budget_outline'],
            'tasks_overdue' => ['label' => 'Overdue tasks reach', 'unit' => 'items', 'default' => 1, 'category' => 'timeline', 'tab' => 'timeline', 'template' => 'planning_timeline'],
            'rsvp_pending' => ['label' => 'Guests awaiting RSVP reach', 'unit' => 'guests', 'default' => 5, 'category' => 'guest', 'tab' => 'guests', 'template' => 'rsvp_reminder_email'],
            'vendor_unconfirmed' => ['label' => 'Unconfirmed vendors reach', 'unit' => 'vendors', 'default' => 1, 'category' => 'vendor', 'tab' => 'vendors', 'template' => 'vendor_brief'],
            'days_until' => ['label' => 'Days until the event fall to', 'unit' => 'days', 'default' => 14, 'category' => 'planning', 'tab' => 'tasks', 'template' => 'run_of_show'],
            'outstanding_invoices' => ['label' => 'Outstanding invoices reach (TZS)', 'unit' => 'TZS', 'default' => 1, 'category' => 'financial', 'tab' => 'finance', 'template' => 'client_update_email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function actions(): array
    {
        // Advisory actions, then the performing actions (which queue an approval),
        // then the passive flag.
        return array_merge(
            [
                'recommend' => 'Raise a recommendation',
                'draft_document' => 'Draft a document',
            ],
            [
                'send_rsvp_reminders' => 'Send RSVP reminders (needs approval)',
                'send_invitations' => 'Send invitations (needs approval)',
                'create_tasks' => 'Add the planning checklist (needs approval)',
            ],
            ['flag' => 'Flag it only'],
        );
    }

    /**
     * Evaluate every enabled rule for the planner (optionally scoped to one
     * event) and return the fires produced this run.
     *
     * @return array<int, array<string, mixed>>
     */
    public function evaluate(User $user, ?Event $only = null): array
    {
        $rules = AiAutomationRule::where('user_id', $user->id)->where('enabled', true)->get();
        $fires = [];

        foreach ($rules as $rule) {
            $events = $this->targetEvents($user, $rule, $only);

            foreach ($events as $event) {
                $context = $this->contextBuilder->forEvent($user, $event);
                if ($context === null) {
                    continue;
                }

                $check = $this->check($rule, $context);
                if (! $check['met']) {
                    continue;
                }

                // Dedupe: at most one fire per rule per event per day.
                $already = AiAutomationRun::where('ai_automation_rule_id', $rule->id)
                    ->where('event_id', $event->id)
                    ->where('created_at', '>=', Carbon::now()->subDay())
                    ->exists();
                if ($already) {
                    continue;
                }

                $fires[] = $this->act($user, $rule, $event, $context, $check);
            }

            $rule->forceFill(['last_evaluated_at' => now()]);
            if (collect($fires)->contains(fn ($f) => $f['rule_id'] === $rule->id)) {
                $rule->forceFill(['last_fired_at' => now()]);
            }
            $rule->save();
        }

        return $fires;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{met:bool, value:int|float, message:string}
     */
    private function check(AiAutomationRule $rule, array $context): array
    {
        $meta = self::triggers()[$rule->trigger_type] ?? null;
        $threshold = $rule->threshold ?? ($meta['default'] ?? 0);

        return match ($rule->trigger_type) {
            'budget_over' => $this->result(
                ($context['budget']['total'] ?? 0) > 0 && ($context['budget']['utilization_pct'] ?? 0) >= $threshold,
                $context['budget']['utilization_pct'] ?? 0,
                "Budget utilisation is {$this->num($context['budget']['utilization_pct'] ?? 0)}% (threshold {$this->num($threshold)}%).",
            ),
            'tasks_overdue' => $this->result(
                ($context['timeline']['overdue_count'] ?? 0) >= $threshold,
                $context['timeline']['overdue_count'] ?? 0,
                ($context['timeline']['overdue_count'] ?? 0) . " task(s)/milestone(s) overdue (threshold {$this->num($threshold)}).",
            ),
            'rsvp_pending' => $this->result(
                ($context['guests']['total'] ?? 0) > 0 && ($context['guests']['pending'] ?? 0) >= $threshold,
                $context['guests']['pending'] ?? 0,
                ($context['guests']['pending'] ?? 0) . " guest(s) awaiting RSVP (threshold {$this->num($threshold)}).",
            ),
            'vendor_unconfirmed' => $this->result(
                ($context['vendors']['pending'] ?? 0) >= $threshold,
                $context['vendors']['pending'] ?? 0,
                ($context['vendors']['pending'] ?? 0) . " vendor(s) unconfirmed (threshold {$this->num($threshold)}).",
            ),
            'days_until' => $this->result(
                ($context['event']['days_until'] ?? null) !== null
                    && $context['event']['days_until'] >= 0
                    && $context['event']['days_until'] <= $threshold,
                $context['event']['days_until'] ?? 0,
                ($context['event']['days_until'] ?? 0) . " day(s) until the event (threshold {$this->num($threshold)}).",
            ),
            'outstanding_invoices' => $this->result(
                ($context['finance']['outstanding_amount'] ?? 0) >= $threshold,
                $context['finance']['outstanding_amount'] ?? 0,
                'TZS ' . number_format($context['finance']['outstanding_amount'] ?? 0, 0) . ' outstanding.',
            ),
            default => ['met' => false, 'value' => 0, 'message' => ''],
        };
    }

    /**
     * Perform the rule's action and log the run.
     *
     * @param  array<string, mixed>  $context
     * @param  array{met:bool, value:int|float, message:string}  $check
     * @return array<string, mixed>
     */
    private function act(User $user, AiAutomationRule $rule, Event $event, array $context, array $check): array
    {
        $resultType = null;
        $resultId = null;

        if ($rule->action_type === 'recommend') {
            $rec = $this->raiseRecommendation($user, $rule, $event, $check);
            if ($rec) { $resultType = 'recommendation'; $resultId = $rec->id; }
        } elseif ($rule->action_type === 'draft_document') {
            $doc = $this->draftDocument($user, $rule, $event);
            if ($doc) { $resultType = 'document'; $resultId = $doc->id; }
        } elseif (ActionExecutor::isKnown($rule->action_type)) {
            // Performing action: queue it for the planner's one-click approval,
            // but only when there's actually something to do.
            $preview = $this->executor->preview($user, $rule->action_type, [], $event);
            if (($preview['count'] ?? 0) > 0) {
                $queued = $this->executor->queue($user, $rule->action_type, [], [
                    'source' => 'automation',
                    'event_id' => $event->id,
                ]);
                $resultType = 'action'; $resultId = $queued->id;
            }
        }

        $run = AiAutomationRun::create([
            'ai_automation_rule_id' => $rule->id,
            'user_id' => $user->id,
            'event_id' => $event->id,
            'summary' => $check['message'],
            'action_type' => $rule->action_type,
            'result_type' => $resultType,
            'result_id' => $resultId,
        ]);

        return [
            'id' => $run->id,
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'event_id' => $event->id,
            'event_title' => $event->title,
            'summary' => $check['message'],
            'action_type' => $rule->action_type,
            'result_type' => $resultType,
            'result_id' => $resultId,
            'created_at' => $run->created_at?->toIso8601String(),
        ];
    }

    private function raiseRecommendation(User $user, AiAutomationRule $rule, Event $event, array $check): ?AiRecommendation
    {
        $meta = self::triggers()[$rule->trigger_type] ?? [];
        $signature = md5("auto|{$rule->id}|{$event->id}|{$rule->trigger_type}");

        $existing = AiRecommendation::where('event_id', $event->id)->where('signature', $signature)->first();
        if ($existing && $existing->status !== RecommendationStatus::Pending) {
            return $existing; // respect the planner's triage — don't resurface
        }

        return AiRecommendation::updateOrCreate(
            ['event_id' => $event->id, 'signature' => $signature],
            [
                'user_id' => $user->id,
                'category' => $meta['category'] ?? 'planning',
                'title' => $rule->name,
                'description' => $check['message'] . ' Raised automatically by your "' . $rule->name . '" rule.',
                'priority' => $rule->action_config['priority'] ?? 'high',
                'confidence' => 90,
                'action_label' => 'Open ' . ($meta['tab'] ?? 'event'),
                'action_type' => 'navigate',
                'action_payload' => ['tab' => $meta['tab'] ?? null],
                'status' => RecommendationStatus::Pending->value,
            ],
        );
    }

    private function draftDocument(User $user, AiAutomationRule $rule, Event $event): ?AiGeneratedDocument
    {
        $meta = self::triggers()[$rule->trigger_type] ?? [];
        $key = $rule->action_config['template_key'] ?? ($meta['template'] ?? null);
        $definition = $key ? $this->catalog->find($key) : null;
        if (! $definition) {
            return null;
        }

        $result = $this->generator->generate($user, $definition, [], $event);
        $template = AiTemplate::where('key', $definition['key'])->first();

        return AiGeneratedDocument::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ai_template_id' => $template?->id,
            'template_key' => $definition['key'],
            'category' => $definition['category'],
            'title' => "{$definition['name']} — {$event->title} (automated)",
            'format' => 'markdown',
            'content' => $result['content'],
            'inputs' => [],
            'status' => DocumentStatus::Draft->value,
            'model' => $result['model'],
            'meta' => [
                'grounded' => $result['grounded'],
                'driver' => $result['model'] === 'local-composer' ? 'local' : 'live',
                'event_id' => $event->id,
                'automated' => true,
            ],
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event>
     */
    private function targetEvents(User $user, AiAutomationRule $rule, ?Event $only)
    {
        if ($only) {
            return $rule->event_id && $rule->event_id !== $only->id ? collect() : collect([$only]);
        }

        if ($rule->event_id) {
            $event = Event::where('planner_id', $user->id)->find($rule->event_id);

            return $event ? collect([$event]) : collect();
        }

        return Event::where('planner_id', $user->id)
            ->whereNotIn('status', ['archived', 'cancelled', 'completed'])
            ->get();
    }

    /**
     * @return array{met:bool, value:int|float, message:string}
     */
    private function result(bool $met, int|float $value, string $message): array
    {
        return ['met' => $met, 'value' => $value, 'message' => $message];
    }

    private function num(int|float $n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    }
}
