<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiMemory;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The AI Orchestrator. For a chat turn it: determines intent, gathers the
 * relevant (permission-filtered) event context and saved memory, routes to the
 * right specialised agent persona, calls the active provider, then merges and
 * persists the result. Every assistant turn is tagged with the agent, model and
 * whether it was grounded in real data, so AI output is always distinguishable
 * and auditable.
 */
class Orchestrator
{
    public function __construct(
        private readonly AiManager $ai,
        private readonly EventContextBuilder $contextBuilder,
        private readonly KnowledgeRetriever $knowledge,
        private readonly CommandParser $commands,
        private readonly ActionExecutor $executor,
        private readonly PlannerHistoryService $history,
    ) {}

    /**
     * Handle one user turn on a conversation and return the assistant's reply.
     */
    public function chat(User $user, AiConversation $conversation, string $message): AiMessage
    {
        // Persist the user's turn first.
        $conversation->messages()->create(['role' => 'user', 'content' => $message]);

        // If the message reads as a command the copilot can perform, propose it
        // as an approval card instead of just answering.
        if ($command = $this->commands->parse($message)) {
            return $this->proposeAction($user, $conversation, $message, $command);
        }

        $context = $this->gatherContext($user, $conversation);

        // Retrieve the planner's own knowledge relevant to this question so the
        // provider can cite it alongside the live event data.
        $knowledge = $this->knowledge->retrieve($user, $conversation->event_id, $message);
        if (! empty($knowledge)) {
            $context['knowledge'] = $knowledge;
        }

        $agent = $this->routeAgent($message, $conversation);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        $result = $this->ai->provider()->chat($this->systemPrompt($user, $agent), $history, $context);

        $assistant = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'agent' => $agent,
            'model' => $result['model'],
            'meta' => [
                'grounded' => ! empty($context['event']) || ! empty($context['knowledge']),
                'driver' => $this->ai->driver(),
                'event_id' => $conversation->event_id,
                'cited_knowledge' => count($context['knowledge'] ?? []),
            ],
        ]);

        $this->touchConversation($conversation, $message);

        return $assistant;
    }

    /**
     * The message was a command. Queue the matching action for approval and reply
     * with a short proposal the chat renders as an approval card. Event-scoped
     * commands need the conversation to be tied to an event.
     *
     * @param  array{type:string, params:array<string,mixed>, needs_event:bool}  $command
     */
    private function proposeAction(User $user, AiConversation $conversation, string $message, array $command): AiMessage
    {
        if ($command['needs_event'] && ! $conversation->event_id) {
            $label = Str::lower(ActionExecutor::label($command['type']));
            $assistant = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => "To **{$label}**, open OSEP AI from the event you mean (its workspace chat), so I know whose guests to act on.",
                'agent' => 'action',
                'model' => 'command',
                'meta' => ['driver' => $this->ai->driver()],
            ]);
            $this->touchConversation($conversation, $message);

            return $assistant;
        }

        $preview = $this->executor->preview($user, $command['type'], $command['params'], $conversation->event);

        $assistant = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => "I can do that — **{$preview['title']}**.\n\n{$preview['summary']}\n\nApprove below and I'll run it. Nothing goes out until you do.",
            'agent' => 'action',
            'model' => 'command',
            'meta' => ['driver' => $this->ai->driver(), 'has_action' => true],
        ]);

        $this->executor->queue($user, $command['type'], $command['params'], [
            'source' => 'chat',
            'event_id' => $conversation->event_id,
            'ai_conversation_id' => $conversation->id,
            'ai_message_id' => $assistant->id,
        ]);

        $this->touchConversation($conversation, $message);

        return $assistant->load('action.event:id,title');
    }

    /**
     * Assemble the grounding context: event snapshot (if any) plus saved memory.
     *
     * @return array<string, mixed>
     */
    public function gatherContext(User $user, AiConversation $conversation): array
    {
        $context = [];

        if ($conversation->event) {
            $built = $this->contextBuilder->forEvent($user, $conversation->event);
            if ($built) {
                $context = $built;

                // The planner's own historical benchmarks, so budget/vendor
                // answers can compare against how they usually work.
                $benchmark = $this->history->compareEvent($user, $conversation->event);
                if ($benchmark) {
                    $context['benchmark'] = $benchmark;
                }
                $quoteFlags = $this->history->quoteFlags($user, $conversation->event);
                if ($quoteFlags) {
                    $context['quote_flags'] = $quoteFlags;
                }
            }
        }

        $memory = $this->recallMemory($user, $conversation->event_id);
        if (! empty($memory)) {
            $context['memory'] = $memory;
        }

        return $context;
    }

    /**
     * Planner preferences plus this event's remembered facts.
     *
     * @return array<int, array{scope: string, label: string, value: string}>
     */
    private function recallMemory(User $user, ?int $eventId): array
    {
        return AiMemory::where('user_id', $user->id)
            ->where(fn ($q) => $q->where('scope', 'planner')
                ->orWhere(fn ($q2) => $q2->where('scope', 'event')->where('event_id', $eventId)))
            ->orderByDesc('pinned')
            ->limit(20)
            ->get()
            ->map(fn (AiMemory $m) => ['scope' => $m->scope, 'label' => $m->label, 'value' => $m->value])
            ->all();
    }

    /**
     * Pick the specialised agent persona best matching the request. This is the
     * Orchestrator's intent-routing step.
     */
    private function routeAgent(string $message, AiConversation $conversation): string
    {
        $p = Str::lower($message);

        return match (true) {
            $this->has($p, ['budget', 'cost', 'spend', 'money', 'expense']) => 'budget',
            $this->has($p, ['vendor', 'supplier', 'photographer', 'caterer', 'quote']) => 'vendor',
            $this->has($p, ['guest', 'rsvp', 'invite', 'seat', 'attend', 'meal']) => 'guest',
            $this->has($p, ['timeline', 'task', 'milestone', 'schedule', 'deadline', 'agenda']) => 'planning',
            $this->has($p, ['analytic', 'insight', 'forecast', 'predict', 'trend']) => 'conversation',
            $conversation->context_type === 'budget' => 'budget',
            $conversation->context_type === 'vendor' => 'vendor',
            default => 'conversation',
        };
    }

    private function systemPrompt(User $user, string $agent): string
    {
        $name = config('ai.assistant_name', 'OSEP AI');
        $persona = match ($agent) {
            'budget' => 'You are the Budget agent: focus on spend, utilization, savings and financial risk.',
            'vendor' => 'You are the Vendor agent: focus on vendor selection, value, readiness and contracts.',
            'guest' => 'You are the Guest agent: focus on RSVPs, attendance, seating and catering.',
            'planning' => 'You are the Planning agent: focus on timeline, milestones, tasks and the critical path.',
            default => 'You are a general planning copilot.',
        };

        return "You are {$name}, an expert event-planning copilot embedded in the OSEP platform, assisting "
            . "{$user->full_name} (an event planner). {$persona}\n\n"
            . "Rules:\n"
            . "- Ground every factual claim in the GROUNDING DATA provided. Never invent figures.\n"
            . "- If data is missing, say so and suggest how the planner can add it.\n"
            . "- When KNOWLEDGE BASE notes are provided, use them and cite the note by its title.\n"
            . "- Be concise, practical and specific. Use Markdown (short headings, bullet lists) for structure.\n"
            . "- Give clear, prioritised next actions.\n"
            . "- Respect the planner's saved memory/preferences when present.";
    }

    private function touchConversation(AiConversation $conversation, string $firstMessage): void
    {
        $updates = ['last_message_at' => now()];

        if ($conversation->title === 'New conversation') {
            $updates['title'] = Str::limit(trim($firstMessage), 48, '…');
        }

        $conversation->update($updates);
    }

    private function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
