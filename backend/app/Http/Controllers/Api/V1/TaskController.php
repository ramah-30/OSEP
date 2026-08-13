<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Http\Controllers\Api\V1\Concerns\AuthorizesEventAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreTaskCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Event;
use App\Models\EventTask;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse, AuthorizesEventAccess;

    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $tasks = $event->tasks()
            ->with(['assignee', 'dependencies'])
            ->withCount('comments')
            ->get();

        return $this->success([
            'tasks' => TaskResource::collection($tasks),
        ]);
    }

    public function store(StoreTaskRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();

        $task = $event->tasks()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'status' => $data['status'] ?? TaskStatus::NotStarted->value,
            'due_date' => $data['due_date'] ?? null,
            'position' => (int) $event->tasks()->max('position') + 1,
        ]);

        if (! empty($data['depends_on'])) {
            $task->dependencies()->sync($this->ownDependencies($event, $data['depends_on']));
        }

        $this->activity->log($event, $request->user(), 'task_created', "added task \"{$task->title}\"", $task);

        return $this->created([
            'task' => new TaskResource($task->load(['assignee', 'dependencies'])),
        ], 'Task created.');
    }

    public function update(UpdateTaskRequest $request, Event $event, EventTask $task): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $task);

        $data = $request->validated();
        $wasCompleted = $task->status === TaskStatus::Completed;

        $task->fill($data);

        // Keep completed_at in step with the status.
        if ($task->status === TaskStatus::Completed && ! $wasCompleted) {
            $task->completed_at = now();
        } elseif ($task->status !== TaskStatus::Completed) {
            $task->completed_at = null;
        }

        $task->save();

        if (array_key_exists('depends_on', $data)) {
            $task->dependencies()->sync($this->ownDependencies($event, $data['depends_on'] ?? []));
        }

        $event->recalculateProgress();

        if ($task->status === TaskStatus::Completed && ! $wasCompleted) {
            $this->activity->log($event, $request->user(), 'task_completed', "completed task \"{$task->title}\"", $task);
        }

        return $this->success([
            'task' => new TaskResource($task->load(['assignee', 'dependencies'])),
        ], 'Task updated.');
    }

    public function destroy(Request $request, Event $event, EventTask $task): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $task);

        $task->delete();
        $event->recalculateProgress();

        return $this->success(null, 'Task deleted.');
    }

    /**
     * Persist a Kanban drag: move one task to a new column and re-index the
     * order of that column.
     */
    public function reorder(ReorderTasksRequest $request, Event $event): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);

        $data = $request->validated();
        $status = TaskStatus::from($data['status']);

        $moved = $event->tasks()->whereKey($data['task_id'])->first();
        abort_unless($moved !== null, 404);

        $wasCompleted = $moved->status === TaskStatus::Completed;
        $moved->status = $status;
        $moved->completed_at = $status === TaskStatus::Completed ? ($moved->completed_at ?? now()) : null;
        $moved->save();

        // Re-index positions in the target column, ignoring ids from other events.
        $ownIds = $event->tasks()->whereIn('id', $data['ordered_ids'])->pluck('id')->all();
        foreach ($data['ordered_ids'] as $index => $id) {
            if (in_array($id, $ownIds, true)) {
                $event->tasks()->whereKey($id)->update(['position' => $index]);
            }
        }

        $event->recalculateProgress();

        if ($status === TaskStatus::Completed && ! $wasCompleted) {
            $this->activity->log($event, $request->user(), 'task_completed', "completed task \"{$moved->title}\"", $moved);
        }

        return $this->success([
            'tasks' => TaskResource::collection(
                $event->tasks()->with(['assignee', 'dependencies'])->withCount('comments')->get()
            ),
        ], 'Board updated.');
    }

    public function addComment(StoreTaskCommentRequest $request, Event $event, EventTask $task): JsonResponse
    {
        $this->ensurePlannerOwns($request, $event);
        $this->ensureBelongsToEvent($event, $task);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        return $this->created([
            'comment' => new TaskCommentResource($comment->load('author')),
        ], 'Comment added.');
    }

    /**
     * Keep only dependency ids that belong to the same event.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function ownDependencies(Event $event, array $ids): array
    {
        return $event->tasks()->whereIn('id', $ids)->pluck('id')->all();
    }
}
