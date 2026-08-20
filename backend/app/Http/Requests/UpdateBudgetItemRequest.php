<?php

namespace App\Http\Requests;

use App\Enums\BudgetItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetItemRequest extends FormRequest
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
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'estimated_cost' => ['sometimes', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::enum(BudgetItemStatus::class)],
            'vendor_assigned_id' => ['nullable', 'integer', 'exists:vendors_assigned,id'],
        ];
    }
}
