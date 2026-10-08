<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;

/**
 * @group Categories
 */
class CategoryController extends Controller
{
    use ApiResponse;

    private const CACHE_TTL = 3600;

    public function index()
    {
        $categories = Cache::remember(Category::CACHE_KEY, self::CACHE_TTL, function () {
            return Category::orderBy('name')->get();
        });

        return $this->resourceResponse(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->authorize('create', Category::class);

        $category = Category::create($request->validated());

        return $this->resourceResponse(new CategoryResource($category), 'Category created successfully', 201);
    }

    public function show(Category $category)
    {
        return $this->resourceResponse(new CategoryResource($category), 'Category retrieved successfully');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);

        $category->update($request->validated());

        return $this->resourceResponse(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }
}
