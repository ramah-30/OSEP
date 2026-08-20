<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
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
            'locale' => ['required', 'string', 'max:12'],
            'timezone' => ['required', 'string', 'timezone'],
            'theme' => ['required', Rule::in(['system', 'light', 'dark'])],
        ];
    }
}
