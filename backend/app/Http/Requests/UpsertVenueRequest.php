<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertVenueRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'setting' => ['nullable', Rule::in(['indoor', 'outdoor', 'mixed'])],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'parking_available' => ['nullable', 'boolean'],
            'setup_time' => ['nullable', 'date_format:H:i'],
            'breakdown_time' => ['nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
