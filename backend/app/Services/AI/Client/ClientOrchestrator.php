<?php

namespace App\Services\AI\Client;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\AI\AiManager;
use Illuminate\Support\Str;

/**
 * The client concierge's orchestrator. Gathers the client's permission-filtered
 * snapshot, routes to the active provider - the offline client engine, or the
 * shared hosted model in live mode - and persists the exchange. Separate from
 * the planner and vendor orchestrators so each role's copilot evolves on its
 * own, while all three share the provider layer and conversation storage.
 */
class ClientOrchestrator
{
    public function __construct(
        private readonly AiManager $ai,
        private readonly ClientContextBuilder $contextBuilder,
        private readonly ClientCommandParser $commands,
        private readonly ClientActionExecutor $executor,
    ) {}

    public function chat(User $client, AiConversation $conversation, string $message): AiMessage
    {
        $conversation->messages()->create(['role' => 'user', 'content' => $message]);

        // If the message reads as a command the concierge can perform, propose it
        // as an approval card instead of just answering.
        if ($command = $this->commands->parse($message)) {
            return $this->proposeAction($client, $conversation, $message, $command);
        }

        $context = $this->contextBuilder->forClient($client);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        $provider = $this->ai->isLive() ? $this->ai->provider() : new ClientLocalProvider();

        $result = $provider->chat($this->systemPrompt($client), $history, $context);

        $assistant = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'agent' => 'client',
            'model' => $result['model'],
            'meta' => [
                'grounded' => ! empty($context['event']),
                'driver' => $this->ai->driver(),
                'workspace' => 'client',
            ],
        ]);

        $this->touchConversation($conversation, $message);

        return $assistant;
    }

    /**
     * The message was a command. Queue the matching action for approval (only when
     * it resolves to something) and reply with a short proposal the chat renders
     * as an approval card.
     *
     * @param  array{type:string, params:array<string,mixed>}  $command
     */
    private function proposeAction(User $client, AiConversation $conversation, string $message, array $command): AiMessage
    {
        $preview = $this->executor->preview($client, $command['type'], $command['params']);

        if (($preview['count'] ?? 0) < 1) {
            $assistant = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $preview['summary'],
                'agent' => 'client',
                'model' => 'command',
                'meta' => ['driver' => $this->ai->driver(), 'workspace' => 'client'],
            ]);
            $this->touchConversation($conversation, $message);

            return $assistant;
        }

        $assistant = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => "I can do that - **{$preview['title']}**.\n\n{$preview['summary']}\n\nApprove below and I'll take care of it. Nothing happens until you do.",
            'agent' => 'client',
            'model' => 'command',
            'meta' => ['driver' => $this->ai->driver(), 'has_action' => true, 'workspace' => 'client'],
        ]);

        $this->executor->queue($client, $command['type'], $command['params'], [
            'source' => 'chat',
            'ai_conversation_id' => $conversation->id,
            'ai_message_id' => $assistant->id,
        ]);

        $this->touchConversation($conversation, $message);

        return $assistant->load('action');
    }

    private function systemPrompt(User $client): string
    {
        $name = config('ai.client_assistant_name', 'OSEP Planning Concierge');

        return "You are the {$name}, a warm, reassuring event-planning concierge embedded in the OSEP platform, "
            . "assisting {$client->full_name}, a client planning their event. Help them stay on top of their event: "
            . "progress, approvals they owe, payments due, their guest list and RSVPs, and updates from their planner.\n\n"
            . "Rules:\n"
            . "- Ground every factual claim in the GROUNDING DATA provided. Never invent figures.\n"
            . "- If data is missing, say so gently and suggest what they can do.\n"
            . "- Be warm, clear and encouraging. Use plain language and Markdown (short headings, bullet lists).\n"
            . "- Always end with the single most useful next step, when there is one.";
    }

    private function touchConversation(AiConversation $conversation, string $firstMessage): void
    {
        $updates = ['last_message_at' => now()];

        if ($conversation->title === 'New conversation') {
            $updates['title'] = Str::limit(trim($firstMessage), 48, '...');
        }

        $conversation->update($updates);
    }
}
