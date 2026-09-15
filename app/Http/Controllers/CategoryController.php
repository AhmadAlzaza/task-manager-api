<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse; // 👈 استيراد الـ Trait
use Illuminate\Support\Facades\Cache;

/**
 * @group Categories
 * القراءة (index, show) لأي مستخدم مسجّل.
 * الكتابة (store, update, destroy) لـ role=admin فقط — 403 لغيره.
 */
class CategoryController extends Controller
{
    use ApiResponse; // 👈 تفعيل الـ Trait

    private const CACHE_KEY = 'categories.all';

    private const CACHE_TTL = 3600;

    public function index()
    {
        $categories = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Category::orderBy('name')->get();
        });

        return $this->resourceResponse(CategoryResource::collection($categories), 'Categories retrieved successfully');
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->authorize('create', Category::class); // 👈 حماية المسار للمدير فقط

        $category = Category::create($request->validated());

        Cache::forget(self::CACHE_KEY);

        return $this->resourceResponse(new CategoryResource($category), 'Category created successfully', 201);
    }

    public function show(Category $category)
    {
        return $this->resourceResponse(new CategoryResource($category), 'Category retrieved successfully');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category); // 👈 حماية المسار للمدير فقط

        $category->update($request->validated());

        Cache::forget(self::CACHE_KEY);

        return $this->resourceResponse(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category); // 👈 حماية المسار للمدير فقط

        $category->delete();

        Cache::forget(self::CACHE_KEY);

        return $this->successResponse(null, 'Category deleted successfully');
    }
}
