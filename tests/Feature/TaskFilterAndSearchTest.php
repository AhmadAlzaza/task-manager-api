<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterAndSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_tasks_by_title_or_description()
    {
        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Learn Laravel Architecture',
            'description' => 'Advanced backend development',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Buy groceries',
            'description' => 'Milk and bread',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?search=Laravel');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Learn Laravel Architecture');
    }

    public function test_user_can_filter_tasks_by_category()
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $task1 = Task::factory()->create(['user_id' => $user->id]);
        $task1->categories()->attach($categoryA);

        $task2 = Task::factory()->create(['user_id' => $user->id]);
        $task2->categories()->attach($categoryB);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/tasks?category_id={$categoryA->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $task1->id);
    }

    public function test_user_can_sort_tasks_by_due_date()
    {
        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDays(5),
        ]);

        $latestTask = Task::factory()->create([
            'user_id' => $user->id,
            'due_date' => now()->addDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?sort_by=due_date&sort_direction=asc');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $latestTask->id);
    }
}
