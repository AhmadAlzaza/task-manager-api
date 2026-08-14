<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_categories()
    {
        $user = User::factory()->create();
        Category::factory(3)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_single_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    }

    public function test_user_can_create_category()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => $category->name,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }

    public function test_user_can_update_category()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $newName = fake()->word();
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => $newName,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', $newName);
    }

    public function test_user_can_delete_category()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Category deleted successfully']);
    }

    public function test_user_cannot_create_category()
    {
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/categories', [
            'name' => $category->name,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_category()
    {
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::factory()->create();
        $newName = fake()->word();
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => $newName,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_category()
    {
        $user = User::factory()->create(['role' => 'user']);
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(403);
    }
}
