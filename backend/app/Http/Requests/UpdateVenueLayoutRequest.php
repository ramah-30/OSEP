<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Updates a layout's venue properties and, when `objects` is present, bulk-syncs
 * the whole canvas (the autosave payload).
 */
class UpdateVenueLayoutRequest extends FormRequest
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
            'layout_name' => ['sometimes', 'required', 'string', 'max:255'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_type' => ['nullable', 'string', 'max:120'],
            'setting' => ['nullable', Rule::in(['indoor', 'outdoor', 'mixed'])],
            'width' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'height' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
            'unit' => ['nullable', 'string', 'max:8'],
            'max_capacity' => ['nullable', 'integer', 'min:0'],
            'entry_points' => ['nullable', 'integer', 'min:0', 'max:255'],
            'exit_points' => ['nullable', 'integer', 'min:0', 'max:255'],
            'meta' => ['nullable', 'array'],

            'objects' => ['sometimes', 'array'],
            'objects.*.uid' => ['required', 'string', 'max:40'],
            'objects.*.object_type' => ['required', 'string', 'max:80'],
            'objects.*.object_name' => ['nullable', 'string', 'max:120'],
            'objects.*.x' => ['required', 'numeric'],
            'objects.*.y' => ['required', 'numeric'],
            'objects.*.width' => ['required', 'numeric', 'min:0'],
            'objects.*.height' => ['required', 'numeric', 'min:0'],
            'objects.*.rotation' => ['nullable', 'numeric'],
            'objects.*.color' => ['nullable', 'string', 'max:40'],
            'objects.*.layer' => ['nullable', 'string', 'max:40'],
            'objects.*.properties' => ['nullable', 'array'],
        ];
    }
}
