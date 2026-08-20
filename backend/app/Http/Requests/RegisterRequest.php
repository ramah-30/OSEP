<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'min:7', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'account_type' => ['required', Rule::in(AccountType::values())],
            'country' => ['required', 'string', 'max:100'],
            'terms' => ['accepted'],
            'category_id' => ['nullable', 'integer', 'exists:vendor_categories,id'],
            'category_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $isVendor = $this->input('account_type') === AccountType::Vendor->value;
            $hasCategory = $this->filled('category_id') || $this->filled('category_name');

            if ($isVendor && ! $hasCategory) {
                $validator->errors()->add('category_id', 'Choose a business category, or add your own.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number, digits and + - ( ) only.',
            'account_type.in' => 'Choose one of the available account types.',
            'terms.accepted' => 'You must accept the Terms of Service and Privacy Policy.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
    }
}
