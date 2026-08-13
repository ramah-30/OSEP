<?php

namespace App\Services\AI\Providers;

/**
 * Turns the structured, permission-filtered platform context into a compact
 * text block the real LLM providers append to the system prompt. Keeping this
 * in one place means every hosted provider grounds answers identically.
 */
trait SerializesContext
{
    protected function contextBlock(array $context): string
    {
        if (empty($context)) {
            return "CONTEXT: No specific event is selected. Answer general event-planning questions helpfully "
                . "and, when useful, suggest the planner select an event so you can use its live data.";
        }

        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "GROUNDING DATA (the planner's real, authorized platform data — base every factual claim on this and "
            . "never invent figures):\n{$json}";
    }
}
