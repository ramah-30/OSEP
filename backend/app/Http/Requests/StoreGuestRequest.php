<?php

namespace App\Http\Requests;

use App\Enums\RsvpStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
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
            // full_name OR (first_name/last_name) - the model reconciles the two.
            'full_name' => ['required_without:first_name', 'nullable', 'string', 'max:255'],
            'first_name' => ['required_without:full_name', 'nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:100'],
            'rsvp_status' => ['nullable', Rule::enum(RsvpStatus::class)],
            'meal_preference' => ['nullable', 'string', 'max:255'],
            'dietary_restrictions' => ['nullable', 'string', 'max:1000'],
            'accessibility_requirements' => ['nullable', 'string', 'max:1000'],
            'plus_ones_allowed' => ['nullable', 'integer', 'min:0', 'max:20'],
            'seat_number' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
