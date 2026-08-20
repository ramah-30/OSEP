<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiAutomationRuleResource;
use App\Http\Resources\AiAutomationRunResource;
use App\Models\AiAutomationRule;
use App\Models\AiAutomationRun;
use App\Models\Event;
use App\Services\AI\AutomationEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Automation rules: "when <trigger> crosses <threshold>, have the copilot
 * <action>." Rules are evaluated on demand (the `run` endpoint) against live
 * event data; each fire is logged for the activity feed.
 */
class AutomationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AutomationEngine $engine) {}

    public function index(Request $request): JsonResponse
    {
        $rules = AiAutomationRule::where('user_id', $request->user()->id)
            ->with('event:id,title')
            ->withCount('runs')
            ->latest()
            ->get();

        return $this->success([
            'rules' => AiAutomationRuleResource::collection($rules),
            'runs' => AiAutomationRunResource::collection($this->recentRuns($request)),
            'options' => $this->options(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $rule = AiAutomationRule::create(array_merge($data, ['user_id' => $request->user()->id]));

        return $this->created([
            'rule' => new AiAutomationRuleResource($rule->load('event:id,title')),
        ], 'Automation rule created.');
    }

    public function update(Request $request, AiAutomationRule $automation): JsonResponse
    {
        $this->authorizeRule($request, $automation);
        $automation->update($this->validated($request, partial: true));

        return $this->success([
            'rule' => new AiAutomationRuleResource($automation->fresh('event:id,title')),
        ], 'Automation rule updated.');
    }

    public function destroy(Request $request, AiAutomationRule $automation): JsonResponse
    {
        $this->authorizeRule($request, $automation);
        $automation->delete();

        return $this->success(null, 'Automation rule deleted.');
    }

    /** Evaluate all rules now and return what fired. */
    public function run(Request $request): JsonResponse
    {
        $fires = $this->engine->evaluate($request->user());

        return $this->success([
            'fired' => $fires,
            'runs' => AiAutomationRunResource::collection($this->recentRuns($request)),
        ], count($fires) > 0 ? count($fires) . ' automation(s) fired.' : 'No rules met their conditions.');
    }

    // -----------------------------------------------------------------

    private function recentRuns(Request $request)
    {
        return AiAutomationRun::where('user_id', $request->user()->id)
            ->with(['event:id,title', 'rule:id,name'])
            ->latest()
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        $triggers = [];
        foreach (AutomationEngine::triggers() as $key => $meta) {
            $triggers[] = [
                'value' => $key,
                'label' => $meta['label'],
                'unit' => $meta['unit'],
                'default' => $meta['default'],
            ];
        }

        $actions = [];
        foreach (AutomationEngine::actions() as $key => $label) {
            $actions[] = ['value' => $key, 'label' => $label];
        }

        return ['triggers' => $triggers, 'actions' => $actions];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        $data = $request->validate([
            'name' => $rule(['required', 'string', 'max:120']),
            'trigger_type' => $rule(['required', 'in:' . implode(',', array_keys(AutomationEngine::triggers()))]),
            'action_type' => $rule(['required', 'in:' . implode(',', array_keys(AutomationEngine::actions()))]),
            'threshold' => ['nullable', 'numeric', 'min:0'],
            'event_id' => ['nullable', 'integer'],
            'action_config' => ['nullable', 'array'],
            'enabled' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['event_id'])) {
            $owns = Event::where('planner_id', $request->user()->id)->whereKey($data['event_id'])->exists();
            abort_unless($owns, 422, 'Invalid event.');
        } elseif (array_key_exists('event_id', $data)) {
            $data['event_id'] = null;
        }

        return $data;
    }

    private function authorizeRule(Request $request, AiAutomationRule $rule): void
    {
        abort_unless($rule->user_id === $request->user()->id, 404);
    }
}
