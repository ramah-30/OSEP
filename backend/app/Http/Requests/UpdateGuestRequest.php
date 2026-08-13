<?php

namespace App\Http\Requests;

use App\Enums\CheckinStatus;
use App\Enums\InvitationStatus;
use App\Enums\RsvpStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestRequest extends FormRequest
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
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:100'],
            'rsvp_status' => ['sometimes', Rule::enum(RsvpStatus::class)],
            'invitation_status' => ['sometimes', Rule::enum(InvitationStatus::class)],
            'checkin_status' => ['sometimes', Rule::enum(CheckinStatus::class)],
            'meal_preference' => ['nullable', 'string', 'max:255'],
            'dietary_restrictions' => ['nullable', 'string', 'max:1000'],
            'accessibility_requirements' => ['nullable', 'string', 'max:1000'],
            'plus_ones_allowed' => ['nullable', 'integer', 'min:0', 'max:20'],
            'seat_number' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
