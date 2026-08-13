<?php

namespace App\Http\Requests;

use App\Enums\InvitationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Send or schedule a reminder. Targets either explicit guests or everyone who
 * still hasn't responded (`target: pending`).
 */
class SendReminderRequest extends FormRequest
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
            'target' => ['nullable', Rule::in(['pending', 'selected', 'all'])],
            'guest_ids' => ['required_if:target,selected', 'array'],
            'guest_ids.*' => ['integer'],
            'channel' => ['nullable', Rule::enum(InvitationChannel::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ];
    }
}
