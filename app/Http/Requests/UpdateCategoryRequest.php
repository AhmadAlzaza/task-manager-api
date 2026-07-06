<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @mixin \Illuminate\Http\Request
 */
/**
 * @property string $name
 */
/**
 * @property \App\Models\Category $category
 */
class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $this->category->id,
        ];
    }
}
