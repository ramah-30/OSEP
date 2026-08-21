<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvitationTemplateRequest;
use App\Http\Requests\UpdateInvitationTemplateRequest;
use App\Http\Resources\InvitationTemplateResource;
use App\Models\InvitationTemplate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The invitation template library - global starters (null owner, read-only) plus
 * the planner's own designs. Planner-scoped, not tied to a single event.
 */
class InvitationTemplateController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $templates = InvitationTemplate::query()
            ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', $userId))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->success(['templates' => InvitationTemplateResource::collection($templates)]);
    }

    public function store(StoreInvitationTemplateRequest $request): JsonResponse
    {
        $template = InvitationTemplate::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return $this->created(['template' => new InvitationTemplateResource($template)], 'Template created.');
    }

    public function update(UpdateInvitationTemplateRequest $request, InvitationTemplate $template): JsonResponse
    {
        $this->ensureOwned($request, $template);

        $template->fill($request->validated())->save();

        return $this->success(['template' => new InvitationTemplateResource($template)], 'Template updated.');
    }

    public function destroy(Request $request, InvitationTemplate $template): JsonResponse
    {
        $this->ensureOwned($request, $template);

        $template->delete();

        return $this->success(null, 'Template deleted.');
    }

    public function duplicate(Request $request, InvitationTemplate $template): JsonResponse
    {
        // Anyone may clone a global starter into their own editable copy.
        $copy = $template->replicate(['is_default']);
        $copy->created_by = $request->user()->id;
        $copy->is_default = false;
        $copy->name = $template->name.' (copy)';
        $copy->save();

        return $this->created(['template' => new InvitationTemplateResource($copy)], 'Template duplicated.');
    }

    private function ensureOwned(Request $request, InvitationTemplate $template): void
    {
        abort_unless($template->created_by === $request->user()->id, 403, 'This template cannot be modified.');
    }
}
