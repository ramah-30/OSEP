<?php

namespace App\Services\AI\Vendor;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\AI\AiManager;
use Illuminate\Support\Str;

/**
 * The vendor copilot's orchestrator. For a chat turn it gathers the vendor's
 * permission-filtered business snapshot, routes to the active provider - the
 * offline vendor engine, or the shared hosted model when live mode is on - and
 * persists the exchange. Deliberately separate from the planner Orchestrator so
 * each role's copilot evolves independently, while both share the provider layer
 * and conversation storage.
 */
class VendorOrchestrator
{
    public function __construct(
        private readonly AiManager $ai,
        private readonly VendorContextBuilder $contextBuilder,
        private readonly VendorCommandParser $commands,
        private readonly VendorActionExecutor $executor,
    ) {}

    public function chat(User $vendor, AiConversation $conversation, string $message): AiMessage
    {
        $conversation->messages()->create(['role' => 'user', 'content' => $message]);

        // If the message reads as a command the copilot can perform, propose it as
        // an approval card instead of just answering.
        if ($command = $this->commands->parse($message)) {
            return $this->proposeAction($vendor, $conversation, $message, $command);
        }

        $context = $this->contextBuilder->forVendor($vendor);

        $history = $conversation->messages()
            ->orderBy('created_at')
            ->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        // Offline: the vendor-specific heuristic engine. Live: the shared hosted
        // model, steered by the vendor system prompt + vendor context.
        $provider = $this->ai->isLive() ? $this->ai->provider() : new VendorLocalProvider();

        $result = $provider->chat($this->systemPrompt($vendor), $history, $context);

        $assistant = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $result['content'],
            'agent' => 'vendor',
            'model' => $result['model'],
            'meta' => [
                'grounded' => ! empty($context['vendor']),
                'driver' => $this->ai->driver(),
                'workspace' => 'vendor',
            ],
        ]);

        $this->touchConversation($conversation, $message);

        return $assistant;
    }

    /**
     * The message was a command. Queue the matching action for approval (only when
     * it actually resolves to something) and reply with a short proposal the chat
     * renders as an approval card.
     *
     * @param  array{type:string, params:array<string,mixed>}  $command
     */
    private function proposeAction(User $vendor, AiConversation $conversation, string $message, array $command): AiMessage
    {
        $preview = $this->executor->preview($vendor, $command['type'], $command['params']);

        // Nothing resolved (no matching request/review, or missing detail): answer
        // with the explanation instead of a dead approval card.
        if (($preview['count'] ?? 0) < 1) {
            $assistant = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $preview['summary'],
                'agent' => 'vendor',
                'model' => 'command',
                'meta' => ['driver' => $this->ai->driver(), 'workspace' => 'vendor'],
            ]);
            $this->touchConversation($conversation, $message);

            return $assistant;
        }

        $assistant = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => "I can do that - **{$preview['title']}**.\n\n{$preview['summary']}\n\nApprove below and I'll run it. Nothing happens until you do.",
            'agent' => 'vendor',
            'model' => 'command',
            'meta' => ['driver' => $this->ai->driver(), 'has_action' => true, 'workspace' => 'vendor'],
        ]);

        $this->executor->queue($vendor, $command['type'], $command['params'], [
            'source' => 'chat',
            'ai_conversation_id' => $conversation->id,
            'ai_message_id' => $assistant->id,
        ]);

        $this->touchConversation($conversation, $message);

        return $assistant->load('action');
    }

    private function systemPrompt(User $vendor): string
    {
        $name = config('ai.vendor_assistant_name', 'OSEP Vendor Copilot');
        $business = $vendor->vendorProfile?->business_name ?: $vendor->full_name;

        return "You are {$name}, an expert marketplace-business copilot embedded in the OSEP platform, assisting "
            . "{$business}, an event-services vendor. Focus on helping them win and deliver work: booking requests, "
            . "quotations and win rate, contracts and revenue, reviews and reputation, availability and storefront quality.\n\n"
            . "Rules:\n"
            . "- Ground every factual claim in the GROUNDING DATA provided. Never invent figures.\n"
            . "- If data is missing, say so and suggest how the vendor can add it.\n"
            . "- Be concise, practical and commercially minded. Use Markdown (short headings, bullet lists).\n"
            . "- Always give clear, prioritised next actions that grow the vendor's business.";
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
