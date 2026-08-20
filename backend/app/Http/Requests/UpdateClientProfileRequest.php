<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientProfileRequest extends FormRequest
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
            'preferred_event_types' => ['nullable', 'array', 'max:20'],
            'preferred_event_types.*' => ['string', 'max:60'],
            'communication_preference' => ['nullable', 'string', 'max:60'],
            'location' => ['nullable', 'string', 'max:120'],
        ];
    }
}
