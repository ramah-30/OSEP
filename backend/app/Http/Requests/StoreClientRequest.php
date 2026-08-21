<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A planner creating a client inline from the event flow. The client account is
 * created in a pending state and can claim it later via password reset.
 */
class StoreClientRequest extends FormRequest
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
        // Email is not globally unique here on purpose: the same person can be a
        // client of more than one planner. A matching email is reused (the
        // controller attaches the existing account), so what we guard against is
        // a duplicate *name* on this planner's own roster - see withValidator().
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $planner = $this->user();
            $first = trim((string) $this->input('first_name'));
            $last = trim((string) $this->input('last_name'));

            if (! $planner || $first === '' || $last === '') {
                return;
            }

            // Clients already on this planner's list: their roster plus anyone
            // picked up from an event (mirrors what ClientController@index shows).
            $clientIds = $planner->plannedEvents()->whereNotNull('client_id')->pluck('client_id')
                ->merge($planner->clients()->pluck('users.id'))
                ->unique();

            $duplicate = User::whereIn('id', $clientIds)
                ->whereRaw('LOWER(first_name) = ?', [mb_strtolower($first)])
                ->whereRaw('LOWER(last_name) = ?', [mb_strtolower($last)])
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('first_name', "You already have a client named \"{$first} {$last}\".");
            }
        });
    }
}
