<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real-LLM driver for Anthropic's Messages API. Enabled by setting
 * AI_DRIVER=anthropic and ANTHROPIC_API_KEY in .env. The grounding context is
 * folded into the system prompt so the model answers from platform data, not
 * general knowledge — the same contract the offline engine honours.
 */
class AnthropicProvider implements AiProvider
{
    use SerializesContext;

    public function name(): string
    {
        return 'anthropic';
    }

    public function chat(string $system, array $messages, array $context = []): array
    {
        $config = config('ai.providers.anthropic');

        if (empty($config['key'])) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not set.');
        }

        $systemPrompt = $system . "\n\n" . $this->contextBlock($context);

        $response = Http::timeout(config('ai.timeout', 30))
            ->withHeaders([
                'x-api-key' => $config['key'],
                'anthropic-version' => $config['version'],
            ])
            ->post(rtrim($config['base_url'], '/') . '/messages', [
                'model' => $config['model'],
                'max_tokens' => $config['max_tokens'],
                'system' => $systemPrompt,
                'messages' => array_map(fn ($m) => [
                    'role' => $m['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $m['content'],
                ], $messages),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Anthropic API error: ' . $response->body());
        }

        $content = collect($response->json('content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        return [
            'content' => $content !== '' ? $content : 'No response was generated.',
            'model' => $config['model'],
        ];
    }
}
