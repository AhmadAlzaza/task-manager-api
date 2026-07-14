<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

/**
 * @mixin Request
 */
/**
 * @property string $name
 */
/**
 * @property Category $category
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
            'name' => 'sometimes|required|string|max:255|unique:categories,name,'.$this->category->id,
        ];
    }
}
