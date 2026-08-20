<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real-LLM driver for OpenAI's Chat Completions API. Enabled by setting
 * AI_DRIVER=openai and OPENAI_API_KEY in .env. Grounding context is injected as
 * a system message so answers stay tied to platform data.
 */
class OpenAiProvider implements AiProvider
{
    use SerializesContext;

    public function name(): string
    {
        return 'openai';
    }

    public function chat(string $system, array $messages, array $context = []): array
    {
        $config = config('ai.providers.openai');

        if (empty($config['key'])) {
            throw new RuntimeException('OPENAI_API_KEY is not set.');
        }

        $payload = array_merge(
            [['role' => 'system', 'content' => $system . "\n\n" . $this->contextBlock($context)]],
            array_map(fn ($m) => [
                'role' => $m['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $m['content'],
            ], $messages),
        );

        $response = Http::timeout(config('ai.timeout', 30))
            ->withToken($config['key'])
            ->post(rtrim($config['base_url'], '/') . '/chat/completions', [
                'model' => $config['model'],
                'max_tokens' => $config['max_tokens'],
                'messages' => $payload,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return [
            'content' => $response->json('choices.0.message.content') ?: 'No response was generated.',
            'model' => $config['model'],
        ];
    }
}
