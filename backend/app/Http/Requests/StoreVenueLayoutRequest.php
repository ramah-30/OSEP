<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenueLayoutRequest extends FormRequest
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
            'layout_name' => ['required', 'string', 'max:255'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_type' => ['nullable', 'string', 'max:120'],
            'setting' => ['nullable', Rule::in(['indoor', 'outdoor', 'mixed'])],
            'width' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'unit' => ['nullable', 'string', 'max:8'],
            'max_capacity' => ['nullable', 'integer', 'min:0'],
            'entry_points' => ['nullable', 'integer', 'min:0', 'max:255'],
            'exit_points' => ['nullable', 'integer', 'min:0', 'max:255'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
