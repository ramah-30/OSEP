<?php

namespace App\Http\Requests;

use App\Enums\TemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvitationTemplateRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'type' => ['nullable', Rule::enum(TemplateType::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'background_url' => ['nullable', 'string', 'max:2048'],
            'theme' => ['nullable', 'array'],
            'rsvp_deadline' => ['nullable', 'date'],
        ];
    }
}
