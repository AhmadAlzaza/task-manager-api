<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @mixin Request
 */
class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => [
                'sometimes',
                'string',
                Rule::enum(TaskStatus::class),
            ],
            'due_date' => 'nullable|date',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ];
    }
}
