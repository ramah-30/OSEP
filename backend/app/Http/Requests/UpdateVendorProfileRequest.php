<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorProfileRequest extends FormRequest
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
            'business_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'social_links' => ['nullable', 'array', 'max:10'],
            'social_links.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
