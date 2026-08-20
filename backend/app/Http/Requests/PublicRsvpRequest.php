<?php

namespace App\Http\Requests;

use App\Enums\RsvpResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A guest's submission from the public RSVP page. No auth — the URL token is the
 * credential (resolved in the controller).
 */
class PublicRsvpRequest extends FormRequest
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
            'response' => ['required', Rule::enum(RsvpResponse::class)],
            'additional_guests' => ['nullable', 'integer', 'min:0', 'max:20'],
            'meal_choice' => ['nullable', 'string', 'max:120'],
            'special_requirements' => ['nullable', 'string', 'max:1000'],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
