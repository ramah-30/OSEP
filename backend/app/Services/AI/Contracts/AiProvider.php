<?php

namespace App\Services\AI\Contracts;

/**
 * A pluggable large-language-model backend. Every provider — the offline
 * heuristic engine or a real hosted LLM — answers to the same contract so the
 * Orchestrator never has to know which one is wired in.
 */
interface AiProvider
{
    /**
     * Produce an assistant reply.
     *
     * @param  string  $system   The system prompt (role, tone, guardrails).
     * @param  array<int, array{role: string, content: string}>  $messages
     *         Prior turns plus the new user message, oldest first.
     * @param  array<string, mixed>  $context
     *         Structured, permission-filtered platform data grounding the reply.
     * @return array{content: string, model: string} The reply and the model id used.
     */
    public function chat(string $system, array $messages, array $context = []): array;

    /** A short identifier shown in the UI ("local", "anthropic", …). */
    public function name(): string;
}
