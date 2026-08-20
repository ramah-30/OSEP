<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiConversationResource;
use App\Http\Resources\AiMessageResource;
use App\Http\Resources\AiPromptTemplateResource;
use App\Models\AiConversation;
use App\Models\AiPromptTemplate;
use App\Models\AiPromptVersion;
use App\Models\Event;
use App\Services\AI\Orchestrator;
use App\Services\AI\PromptStarterCatalog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The planner's reusable prompt library. Each template holds a named prompt with
 * {{variables}}; bodies are versioned so any earlier wording can be rolled back
 * to. Running a prompt fills its variables, opens a fresh conversation and routes
 * it through the Orchestrator so the reply is grounded in the event's live data.
 */
class PromptController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly Orchestrator $orchestrator) {}

    public function index(Request $request): JsonResponse
    {
        $prompts = AiPromptTemplate::where('user_id', $request->user()->id)
            ->with(['event:id,title', 'currentVersion'])
            ->withCount('versions')
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        return $this->success([
            'prompts' => AiPromptTemplateResource::collection($prompts),
            'starters' => PromptStarterCatalog::all(),
        ]);
    }

    public function show(Request $request, AiPromptTemplate $prompt): JsonResponse
    {
        $this->authorizePrompt($request, $prompt);
        $prompt->load(['event:id,title', 'currentVersion', 'versions.author:id,first_name,last_name']);

        return $this->success([
            'prompt' => new AiPromptTemplateResource($prompt),
        ]);
    }

    /** Create a template and seed version 1 with the supplied body. */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $prompt = DB::transaction(function () use ($data, $request) {
            $prompt = AiPromptTemplate::create([
                'user_id' => $request->user()->id,
                'event_id' => $data['event_id'],
                'name' => $data['name'],
                'category' => $data['category'] ?? null,
                'description' => $data['description'] ?? null,
                'variables' => AiPromptTemplate::extractVariables($data['body']),
                'current_version' => 1,
            ]);

            $prompt->versions()->create([
                'created_by' => $request->user()->id,
                'version' => 1,
                'body' => $data['body'],
                'note' => 'Initial version',
            ]);

            return $prompt;
        });

        return $this->created([
            'prompt' => new AiPromptTemplateResource($prompt->load(['event:id,title', 'currentVersion'])->loadCount('versions')),
        ], 'Prompt saved to your library.');
    }

    /**
     * Update metadata and, when the body changes, append a new version. Metadata
     * edits alone never create a version.
     */
    public function update(Request $request, AiPromptTemplate $prompt): JsonResponse
    {
        $this->authorizePrompt($request, $prompt);
        $data = $this->validated($request, partial: true);

        DB::transaction(function () use ($data, $prompt, $request) {
            $meta = array_intersect_key($data, array_flip(['name', 'category', 'description', 'event_id', 'pinned']));
            if (! empty($meta)) {
                $prompt->update($meta);
            }

            if (array_key_exists('body', $data) && $data['body'] !== $prompt->currentVersion?->body) {
                $this->appendVersion($prompt, $request->user()->id, $data['body'], $data['note'] ?? 'Edited');
            }
        });

        return $this->success([
            'prompt' => new AiPromptTemplateResource(
                $prompt->fresh(['event:id,title', 'currentVersion'])->loadCount('versions')
            ),
        ], 'Prompt updated.');
    }

    /** Roll back to an earlier version by cloning its body forward as a new version. */
    public function rollback(Request $request, AiPromptTemplate $prompt): JsonResponse
    {
        $this->authorizePrompt($request, $prompt);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);

        $target = $prompt->versions()->where('version', $data['version'])->first();
        abort_unless($target !== null, 422, 'That version no longer exists.');

        $this->appendVersion($prompt, $request->user()->id, $target->body, "Rolled back to v{$target->version}");

        return $this->success([
            'prompt' => new AiPromptTemplateResource(
                $prompt->fresh(['event:id,title', 'currentVersion', 'versions.author:id,first_name,last_name'])->loadCount('versions')
            ),
        ], "Rolled back to version {$target->version}.");
    }

    /**
     * Run the prompt: fill its variables, open a fresh conversation and let the
     * Orchestrator answer it grounded in the chosen event's live data.
     */
    public function run(Request $request, AiPromptTemplate $prompt): JsonResponse
    {
        $this->authorizePrompt($request, $prompt);
        $user = $request->user();

        $input = $request->validate([
            'variables' => ['nullable', 'array'],
            'event_id' => ['nullable', 'integer'],
        ]);

        // An event chosen at run time wins over the template's default event.
        $eventId = $this->resolveEventId($user->id, $input['event_id'] ?? $prompt->event_id);

        $body = $prompt->currentVersion?->body ?? '';
        $message = AiPromptTemplate::render($body, $input['variables'] ?? []);

        $conversation = AiConversation::create([
            'user_id' => $user->id,
            'event_id' => $eventId,
            'context_type' => $eventId ? 'event' : 'general',
            'title' => $prompt->name,
        ]);

        $assistant = $this->orchestrator->chat($user, $conversation, $message);

        $prompt->increment('usage_count');
        $prompt->forceFill(['last_used_at' => now()])->save();

        $conversation->load(['event:id,title']);

        return $this->success([
            'conversation' => new AiConversationResource($conversation),
            'message' => new AiMessageResource($assistant),
        ], 'Prompt run.');
    }

    public function destroy(Request $request, AiPromptTemplate $prompt): JsonResponse
    {
        $this->authorizePrompt($request, $prompt);
        $prompt->delete();

        return $this->success(null, 'Prompt deleted.');
    }

    // -----------------------------------------------------------------

    /** Append a new top version and keep the template's pointer/variables in sync. */
    private function appendVersion(AiPromptTemplate $prompt, int $userId, string $body, string $note): AiPromptVersion
    {
        $next = (int) $prompt->versions()->max('version') + 1;

        $version = $prompt->versions()->create([
            'created_by' => $userId,
            'version' => $next,
            'body' => $body,
            'note' => $note,
        ]);

        $prompt->update([
            'current_version' => $next,
            'variables' => AiPromptTemplate::extractVariables($body),
        ]);

        return $version;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn (array $r) => $partial ? array_merge(['sometimes'], $r) : $r;

        $data = $request->validate([
            'name' => $rule(['required', 'string', 'max:120']),
            'body' => $rule(['required', 'string', 'max:8000']),
            'category' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'event_id' => ['nullable', 'integer'],
            'pinned' => ['sometimes', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]);

        // Only normalise/validate event_id when the field was part of this
        // request (store always sends it; a partial update may omit it).
        if (array_key_exists('event_id', $data)) {
            if (! empty($data['event_id'])) {
                $owns = Event::where('planner_id', $request->user()->id)->whereKey($data['event_id'])->exists();
                abort_unless($owns, 422, 'Invalid event.');
            } else {
                $data['event_id'] = null;
            }
        } elseif (! $partial) {
            $data['event_id'] = null;
        }

        return $data;
    }

    private function resolveEventId(int $userId, ?int $eventId): ?int
    {
        if (! $eventId) {
            return null;
        }

        return Event::where('planner_id', $userId)->whereKey($eventId)->value('id');
    }

    private function authorizePrompt(Request $request, AiPromptTemplate $prompt): void
    {
        abort_unless($prompt->user_id === $request->user()->id, 404);
    }
}
