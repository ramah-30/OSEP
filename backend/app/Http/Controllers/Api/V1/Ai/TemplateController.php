<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiTemplateResource;
use App\Models\AiTemplate;
use App\Services\AI\DocumentTemplateCatalog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The AI template library. System templates are seeded idempotently from the
 * catalog on read, so the gallery is always populated; planners see those plus
 * any of their own.
 */
class TemplateController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DocumentTemplateCatalog $catalog) {}

    public function index(Request $request): JsonResponse
    {
        $this->syncSystemTemplates();

        $templates = AiTemplate::query()
            ->where(fn ($q) => $q->where('is_system', true)->orWhere('user_id', $request->user()->id))
            ->orderByDesc('is_system')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->success([
            'templates' => AiTemplateResource::collection($templates),
        ]);
    }

    /** Upsert the built-in catalog into ai_templates, keyed by stable slug. */
    private function syncSystemTemplates(): void
    {
        foreach ($this->catalog->all() as $def) {
            AiTemplate::updateOrCreate(
                ['key' => $def['key']],
                [
                    'user_id' => null,
                    'category' => $def['category'],
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'icon' => $def['icon'],
                    'output_format' => 'markdown',
                    'body_template' => $def['body_template'] ?? null,
                    'variables' => $def['variables'] ?? [],
                    'requires_event' => $def['requires_event'] ?? false,
                    'is_system' => true,
                    'sort_order' => $def['sort_order'] ?? 0,
                ],
            );
        }
    }
}
