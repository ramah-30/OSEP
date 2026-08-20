<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreGuestsRequest extends FormRequest
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
            'guests' => ['required', 'array', 'min:1', 'max:500'],
            'guests.*.full_name' => ['required_without:guests.*.first_name', 'nullable', 'string', 'max:255'],
            'guests.*.first_name' => ['nullable', 'string', 'max:120'],
            'guests.*.last_name' => ['nullable', 'string', 'max:120'],
            'guests.*.email' => ['nullable', 'email', 'max:255'],
            'guests.*.phone' => ['nullable', 'string', 'max:40'],
            'guests.*.category' => ['nullable', 'string', 'max:100'],
            'guests.*.meal_preference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
