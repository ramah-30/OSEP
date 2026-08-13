<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,csv,txt'],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in([
                'contract', 'quotation', 'invoice', 'floor_plan', 'checklist', 'image', 'other',
            ])],
            'task_id' => ['nullable', 'integer', 'exists:event_tasks,id'],
        ];
    }
}
