<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    private const CACHE_KEY = 'categories.all';

    private const CACHE_TTL = 3600;

    public function index()
    {
        $categories = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Category::orderBy('name')->get();
        });

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        Cache::forget(self::CACHE_KEY);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        Cache::forget(self::CACHE_KEY);

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        Cache::forget(self::CACHE_KEY);

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
