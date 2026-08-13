<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Replaces the seat assignments for a single table object.
 */
class UpdateSeatingRequest extends FormRequest
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
            'seats' => ['present', 'array'],
            'seats.*.seat_number' => ['required', 'integer', 'min:1', 'max:1000'],
            'seats.*.guest_id' => ['nullable', 'integer', 'exists:guests,id'],
            'seats.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
