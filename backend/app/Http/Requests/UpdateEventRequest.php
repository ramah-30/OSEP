<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'event_type' => ['sometimes', 'required', 'string', 'max:100'],
            'event_category' => ['nullable', 'string', 'max:100'],
            'client_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'expected_guests' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'theme' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'budget_total' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
