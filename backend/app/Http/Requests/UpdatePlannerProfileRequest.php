<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlannerProfileRequest extends FormRequest
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
            'company_name' => ['nullable', 'string', 'max:120'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'specialization' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
        ];
    }
}
