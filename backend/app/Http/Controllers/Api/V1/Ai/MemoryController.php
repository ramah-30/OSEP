<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiMemoryResource;
use App\Models\AiMemory;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The planner's AI memory: reusable preferences (planner scope) and per-event
 * facts (event scope). Planners can view, add, edit and delete every entry;
 * the Orchestrator recalls them to personalise responses.
 */
class MemoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $memories = AiMemory::where('user_id', $request->user()->id)
            ->with('event:id,title')
            ->orderByDesc('pinned')
            ->orderBy('scope')
            ->orderByDesc('updated_at')
            ->get();

        return $this->success([
            'memories' => AiMemoryResource::collection($memories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $memory = AiMemory::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'source' => 'manual',
        ]));

        return $this->created([
            'memory' => new AiMemoryResource($memory->load('event:id,title')),
        ], 'Memory saved.');
    }

    public function update(Request $request, AiMemory $memory): JsonResponse
    {
        $this->authorizeMemory($request, $memory);
        $memory->update($this->validated($request, partial: true));

        return $this->success([
            'memory' => new AiMemoryResource($memory->fresh('event:id,title')),
        ], 'Memory updated.');
    }

    public function destroy(Request $request, AiMemory $memory): JsonResponse
    {
        $this->authorizeMemory($request, $memory);
        $memory->delete();

        return $this->success(null, 'Memory deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        $data = $request->validate([
            'scope' => $rule(['required', 'in:planner,event']),
            'label' => $rule(['required', 'string', 'max:120']),
            'value' => $rule(['required', 'string', 'max:2000']),
            'event_id' => ['nullable', 'integer'],
            'pinned' => ['sometimes', 'boolean'],
        ]);

        // An event-scoped memory must reference an event the planner owns.
        if (($data['scope'] ?? $request->input('scope')) === 'event' && ! empty($data['event_id'])) {
            $owns = Event::where('planner_id', $request->user()->id)->whereKey($data['event_id'])->exists();
            abort_unless($owns, 422, 'Invalid event.');
        } else {
            $data['event_id'] = ($data['scope'] ?? null) === 'planner' ? null : ($data['event_id'] ?? null);
        }

        return $data;
    }

    private function authorizeMemory(Request $request, AiMemory $memory): void
    {
        abort_unless($memory->user_id === $request->user()->id, 404);
    }
}
