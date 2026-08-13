<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequestRequest extends FormRequest
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
            'planner_id'      => ['required', 'integer', 'exists:users,id'],
            'event_type'      => ['nullable', 'string', 'max:100'],
            'event_date'      => ['nullable', 'date', 'after_or_equal:today'],
            'expected_guests' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'venue'           => ['nullable', 'string', 'max:200'],
            'location'        => ['nullable', 'string', 'max:200'],
            'message'         => ['nullable', 'string', 'max:2000'],
        ];
    }
}
