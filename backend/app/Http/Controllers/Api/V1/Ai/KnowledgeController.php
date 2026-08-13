<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiKnowledgeDocumentResource;
use App\Models\AiKnowledgeDocument;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The planner's knowledge base. Global notes (event_id null) apply everywhere;
 * event-scoped notes ground answers for a single event. The Orchestrator's
 * KnowledgeRetriever reads these to cite the planner's own material in replies.
 */
class KnowledgeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $documents = AiKnowledgeDocument::where('user_id', $request->user()->id)
            ->when($request->filled('scope') && $request->query('scope') === 'global', fn ($b) => $b->whereNull('event_id'))
            ->when($request->filled('event_id'), fn ($b) => $b->where('event_id', $request->integer('event_id')))
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('content', 'like', "%{$q}%")
                ->orWhere('category', 'like', "%{$q}%")))
            ->with('event:id,title')
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        return $this->success([
            'documents' => AiKnowledgeDocumentResource::collection($documents),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $document = AiKnowledgeDocument::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'source' => 'manual',
        ]));

        return $this->created([
            'document' => new AiKnowledgeDocumentResource($document->load('event:id,title')),
        ], 'Knowledge note saved.');
    }

    public function update(Request $request, AiKnowledgeDocument $knowledge): JsonResponse
    {
        $this->authorizeDoc($request, $knowledge);
        $knowledge->update($this->validated($request, partial: true));

        return $this->success([
            'document' => new AiKnowledgeDocumentResource($knowledge->fresh('event:id,title')),
        ], 'Knowledge note updated.');
    }

    public function destroy(Request $request, AiKnowledgeDocument $knowledge): JsonResponse
    {
        $this->authorizeDoc($request, $knowledge);
        $knowledge->delete();

        return $this->success(null, 'Knowledge note deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        $data = $request->validate([
            'title' => $rule(['required', 'string', 'max:150']),
            'content' => $rule(['required', 'string', 'max:20000']),
            'category' => ['nullable', 'string', 'max:60'],
            'event_id' => ['nullable', 'integer'],
            'pinned' => ['sometimes', 'boolean'],
        ]);

        // An event-scoped note must reference an event the planner owns.
        if (! empty($data['event_id'])) {
            $owns = Event::where('planner_id', $request->user()->id)->whereKey($data['event_id'])->exists();
            abort_unless($owns, 422, 'Invalid event.');
        } elseif (array_key_exists('event_id', $data)) {
            $data['event_id'] = null;
        }

        return $data;
    }

    private function authorizeDoc(Request $request, AiKnowledgeDocument $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 404);
    }
}
