<?php

namespace App\Http\Requests;

use App\Enums\BudgetItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetItemRequest extends FormRequest
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
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::enum(BudgetItemStatus::class)],
            'vendor_assigned_id' => ['nullable', 'integer', 'exists:vendors_assigned,id'],
        ];
    }
}
