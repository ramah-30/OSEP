<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Event;
use App\Models\EventDocument;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        return $this->success([
            'documents' => DocumentResource::collection($event->documents()->with('uploader')->get()),
        ]);
    }

    public function store(StoreDocumentRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $file = $request->file('file');
        $path = $file->store("events/{$event->id}/documents", 'public');
        $name = $request->validated()['name'] ?? $file->getClientOriginalName();

        // Foundation version history: same file name bumps the version number.
        $version = (int) $event->documents()->where('name', $name)->max('version') + 1;

        $document = $event->documents()->create([
            'task_id' => $request->validated()['task_id'] ?? null,
            'uploaded_by' => $request->user()->id,
            'name' => $name,
            'category' => $request->validated()['category'] ?? 'other',
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'version' => $version,
        ]);

        $this->activity->log($event, $request->user(), 'document_uploaded', "uploaded \"{$document->name}\"", $document);

        return $this->created([
            'document' => new DocumentResource($document->load('uploader')),
        ], 'Document uploaded.');
    }

    public function destroy(Request $request, Event $event, EventDocument $document): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $document);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return $this->success(null, 'Document deleted.');
    }
}
