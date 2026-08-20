<?php

namespace App\Http\Requests;

use App\Enums\InvitationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Send (or schedule) invitations to a set of guests, or to the whole list when
 * `all` is true. Shared by the "Invitations" panel and the guest-list bulk send.
 */
class SendInvitationRequest extends FormRequest
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
            'all' => ['nullable', 'boolean'],
            'guest_ids' => ['required_without:all', 'array'],
            'guest_ids.*' => ['integer'],
            'channel' => ['nullable', Rule::enum(InvitationChannel::class)],
            'template_id' => ['nullable', 'integer', 'exists:invitation_templates,id'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ];
    }
}
