<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondBookingRequestRequest extends FormRequest
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
            'decision'     => ['required', 'in:accepted,declined'],
            'planner_note' => ['nullable', 'string', 'max:2000'],
            'quoted_budget' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
