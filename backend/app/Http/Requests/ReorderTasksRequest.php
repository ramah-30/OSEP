<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Payload for a Kanban drag: the moved task, its new column and the ordered list
 * of task ids in that column.
 */
class ReorderTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'integer', 'exists:event_tasks,id'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:event_tasks,id'],
        ];
    }
}
