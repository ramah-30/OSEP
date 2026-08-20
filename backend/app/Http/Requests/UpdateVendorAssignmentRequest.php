<?php

namespace App\Http\Requests;

use App\Enums\VendorAssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorAssignmentRequest extends FormRequest
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
            'vendor_id' => ['nullable', 'integer', 'exists:users,id'],
            'vendor_name' => ['sometimes', 'required', 'string', 'max:255'],
            'service' => ['sometimes', 'required', 'string', 'max:255'],
            'package' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(VendorAssignmentStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
