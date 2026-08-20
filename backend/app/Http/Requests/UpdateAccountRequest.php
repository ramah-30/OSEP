<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'phone' => ['nullable', 'string', 'min:7', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }
}
