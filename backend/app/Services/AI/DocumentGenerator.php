<?php

namespace App\Services\AI;

use App\Models\Event;
use App\Models\User;

/**
 * Produces a document from a template, grounded in the planner's real event
 * data. Mirrors the provider-driver philosophy of the chat engine: with the
 * offline driver it composes deterministically from the structured context via
 * LocalDocumentComposer; with a live driver it hands the same context and a
 * template instruction to the hosted model. Either way the result is grounded
 * and tagged so AI content stays distinguishable and auditable.
 */
class DocumentGenerator
{
    public function __construct(
        private readonly AiManager $ai,
        private readonly EventContextBuilder $contextBuilder,
        private readonly HealthScoreService $health,
        private readonly LocalDocumentComposer $composer,
    ) {}

    /**
     * @param  array<string, mixed>  $definition  the catalog template definition
     * @param  array<string, mixed>  $inputs      variable values from the planner
     * @return array{content:string, model:string, grounded:bool, context:array<string,mixed>}
     */
    public function generate(User $user, array $definition, array $inputs, ?Event $event): array
    {
        $context = $this->buildContext($user, $event);
        $grounded = ! empty($context['event']);

        if ($this->ai->isLive()) {
            $result = $this->ai->provider()->chat(
                $this->systemPrompt($user),
                [['role' => 'user', 'content' => $this->instruction($definition, $inputs)]],
                $context,
            );
            $content = trim($result['content']);
            $model = $result['model'];
        } else {
            $content = $this->composer->compose($definition['key'], $context, $inputs);
            $model = 'local-composer';
        }

        return [
            'content' => $content,
            'model' => $model,
            'grounded' => $grounded,
            'context' => $context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(User $user, ?Event $event): array
    {
        if (! $event) {
            return [];
        }

        $built = $this->contextBuilder->forEvent($user, $event);
        if (! $built) {
            return [];
        }

        [$score, $breakdown] = $this->health->score($built);
        $built['health'] = [
            'score' => $score,
            'label' => $this->health->label($score),
            'breakdown' => $breakdown,
        ];

        return $built;
    }

    private function systemPrompt(User $user): string
    {
        $name = config('ai.assistant_name', 'OSEP AI');

        return "You are {$name}, an expert event-planning copilot embedded in the OSEP platform, "
            . "generating a document for {$user->full_name} (an event planner).\n\n"
            . "Rules:\n"
            . "- Ground every factual claim in the GROUNDING DATA provided. Never invent figures, names or dates.\n"
            . "- Where data is missing, use sensible best-practice guidance and make clear it is a suggestion.\n"
            . "- Output clean, well-structured Markdown ready to hand over. No preamble, no meta commentary.\n"
            . "- Money is in TZS.";
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $inputs
     */
    private function instruction(array $definition, array $inputs): string
    {
        $lines = ["Generate: {$definition['name']}.", $definition['description']];

        if (! empty($definition['body_template'])) {
            $lines[] = "\n{$definition['body_template']}";
        }

        $provided = array_filter($inputs, fn ($v) => $v !== null && $v !== '');
        if ($provided) {
            $lines[] = "\nPlanner-supplied details:";
            foreach ($provided as $key => $value) {
                $label = ucfirst(str_replace('_', ' ', (string) $key));
                $lines[] = "- {$label}: {$value}";
            }
        }

        $lines[] = "\nProduce the finished document in Markdown.";

        return implode("\n", $lines);
    }
}
