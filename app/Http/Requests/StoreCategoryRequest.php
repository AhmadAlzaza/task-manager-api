<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $name
 * @property \App\Models\Category $category
 */
/**
 * @mixin \Illuminate\Http\Request
 */
class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:categories',
        ];
    }
}
