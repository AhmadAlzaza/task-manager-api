<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_all_categories()
    {
        $user = User::factory()->create();
        Category::factory()->count(3)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    }

    public function test_user_can_show_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name'],
            ]);
    }

    public function test_admin_can_create_category()
    {
        // 👇 إنشاء مستخدم بصلاحية مدير
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $categoryData = [
            'name' => 'New Category',
        ];

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Category created successfully',
            ])
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }

    public function test_normal_user_cannot_create_category()
    {
        // 👇 إنشاء مستخدم عادي
        $user = User::factory()->create(['role' => UserRole::USER]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'New Category',
        ]);

        // يجب أن يتم رفضه لأنه ليس مديراً
        $response->assertStatus(403);
    }

    public function test_admin_can_update_category()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $category = Category::factory()->create();
        $newName = 'Updated Category Name';

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => $newName,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category updated successfully',
            ])
            ->assertJsonPath('data.name', $newName);
    }

    public function test_normal_user_cannot_update_category()
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_category()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $category = Category::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);

        // التأكد من أن التصنيف تم حذفه فعلياً من قاعدة البيانات
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_normal_user_cannot_delete_category()
    {
        $user = User::factory()->create(['role' => UserRole::USER]);
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);

        // التأكد من أن التصنيف لم يتم حذفه من قاعدة البيانات
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
