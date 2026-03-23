<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_categories()
    {
        $user = User::factory()->create();
        Category::factory(3)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_category()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/categories', [
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
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/categories/{$category->id}", [
            'name' => $newName
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', $newName);
    }

    public function test_user_can_delete_category()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Category deleted successfully']);
    }
}
