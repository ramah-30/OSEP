<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiGeneratedDocumentResource;
use App\Models\AiGeneratedDocument;
use App\Models\AiTemplate;
use App\Models\Event;
use App\Services\AI\DocumentGenerator;
use App\Services\AI\DocumentTemplateCatalog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Generated documents: the planner's library of AI-produced deliverables plus
 * the single "generate" entry point. Every document is scoped to its planner and
 * grounded in a permission-checked event when one is chosen.
 */
class DocumentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DocumentGenerator $generator,
        private readonly DocumentTemplateCatalog $catalog,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $eventId = $request->integer('event_id') ?: null;

        $documents = AiGeneratedDocument::where('user_id', $request->user()->id)
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->with('event:id,title')
            ->latest()
            ->get();

        return $this->success([
            'documents' => AiGeneratedDocumentResource::collection($documents),
        ]);
    }

    /** Generate a document from a template, grounded in the chosen event. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_key' => ['required', 'string'],
            'event_id' => ['nullable', 'integer'],
            'inputs' => ['nullable', 'array'],
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $definition = $this->catalog->find($data['template_key']);
        abort_if($definition === null, 404, 'Unknown template.');

        $user = $request->user();
        $event = $this->resolveEvent($request, $data['event_id'] ?? null);

        if (($definition['requires_event'] ?? false) && ! $event) {
            throw ValidationException::withMessages([
                'event_id' => 'This template needs an event to ground its content — please choose one.',
            ]);
        }

        $inputs = $this->cleanInputs($data['inputs'] ?? []);
        $this->assertRequiredVariables($definition, $inputs);

        $result = $this->generator->generate($user, $definition, $inputs, $event);

        $template = AiTemplate::where('key', $definition['key'])->first();

        $document = AiGeneratedDocument::create([
            'user_id' => $user->id,
            'event_id' => $event?->id,
            'ai_template_id' => $template?->id,
            'template_key' => $definition['key'],
            'category' => $definition['category'],
            'title' => $data['title'] ?? $this->buildTitle($definition, $event),
            'format' => 'markdown',
            'content' => $result['content'],
            'inputs' => $inputs,
            'status' => DocumentStatus::Draft->value,
            'model' => $result['model'],
            'meta' => [
                'grounded' => $result['grounded'],
                'driver' => $result['model'] === 'local-composer' ? 'local' : 'live',
                'event_id' => $event?->id,
            ],
        ]);

        $document->load('event:id,title');

        return $this->success([
            'document' => new AiGeneratedDocumentResource($document),
        ], 'Document generated.', 201);
    }

    public function show(Request $request, AiGeneratedDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);
        $userId = $request->user()->id;
        $document->load([
            'event:id,title',
            'feedback' => fn ($q) => $q->where('user_id', $userId),
        ]);

        return $this->success([
            'document' => new AiGeneratedDocumentResource($document),
        ]);
    }

    /** Refine a generated document — edit the content, rename or finalise it. */
    public function update(Request $request, AiGeneratedDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:150'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:draft,final'],
        ]);

        $document->update($data);
        $document->load('event:id,title');

        return $this->success([
            'document' => new AiGeneratedDocumentResource($document->fresh('event')),
        ], 'Document updated.');
    }

    public function destroy(Request $request, AiGeneratedDocument $document): JsonResponse
    {
        $this->authorizeDocument($request, $document);
        $document->delete();

        return $this->success(null, 'Document deleted.');
    }

    // -----------------------------------------------------------------

    private function authorizeDocument(Request $request, AiGeneratedDocument $document): void
    {
        abort_unless($document->user_id === $request->user()->id, 404);
    }

    private function resolveEvent(Request $request, ?int $eventId): ?Event
    {
        if (! $eventId) {
            return null;
        }

        return Event::where('planner_id', $request->user()->id)->find($eventId);
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array<string, string>
     */
    private function cleanInputs(array $inputs): array
    {
        $clean = [];
        foreach ($inputs as $key => $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                $clean[(string) $key] = trim((string) $value);
            }
        }

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, string>  $inputs
     */
    private function assertRequiredVariables(array $definition, array $inputs): void
    {
        $missing = [];
        foreach ($definition['variables'] ?? [] as $var) {
            if (($var['required'] ?? false) && empty($inputs[$var['key']] ?? null)) {
                $missing["inputs.{$var['key']}"] = "{$var['label']} is required.";
            }
        }

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function buildTitle(array $definition, ?Event $event): string
    {
        return $event
            ? "{$definition['name']} — {$event->title}"
            : $definition['name'];
    }
}
