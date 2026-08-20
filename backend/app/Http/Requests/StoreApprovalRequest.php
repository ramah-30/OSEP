<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A planner submitting an item for the client to approve.
 */
class StoreApprovalRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in([
                'budget', 'decoration', 'vendor_selection', 'venue', 'event_schedule', 'proposal', 'quotation',
            ])],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
