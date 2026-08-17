<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // المسار محمي بـ sanctum في الروات
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,in_progress,completed'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
