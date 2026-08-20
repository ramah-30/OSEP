<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Guest-list bulk actions. `action` selects the operation; the remaining fields
 * are validated loosely and interpreted per action in the controller.
 */
class BulkGuestActionRequest extends FormRequest
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
            'action' => ['required', Rule::in(['send_invitations', 'update_category', 'assign_table', 'archive', 'delete'])],
            'guest_ids' => ['required', 'array', 'min:1'],
            'guest_ids.*' => ['integer'],
            'category' => ['required_if:action,update_category', 'nullable', 'string', 'max:100'],
            'seat_number' => ['required_if:action,assign_table', 'nullable', 'string', 'max:40'],
            'channel' => ['nullable', 'string'],
        ];
    }
}
