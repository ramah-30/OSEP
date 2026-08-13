<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Check a guest in — by scanned QR `token` or by `guest_id` (manual search).
 */
class CheckinRequest extends FormRequest
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
            'token' => ['required_without:guest_id', 'nullable', 'string', 'max:64'],
            'guest_id' => ['required_without:token', 'nullable', 'integer'],
            'party_size' => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
