<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Self-service account deletion. The user has to type the exact phrase
 * "delete my account" so the destructive action can't fire by accident.
 */
class DeleteAccountRequest extends FormRequest
{
    public const CONFIRMATION_PHRASE = 'delete my account';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the phrase so trailing spaces / casing don't trip up a genuine
     * confirmation, while still requiring the exact words.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirmation' => mb_strtolower(trim((string) $this->input('confirmation'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', Rule::in([self::CONFIRMATION_PHRASE])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type "'.self::CONFIRMATION_PHRASE.'" exactly to confirm.',
            'confirmation.required' => 'Type "'.self::CONFIRMATION_PHRASE.'" to confirm.',
        ];
    }
}
