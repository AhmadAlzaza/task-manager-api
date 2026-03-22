<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/tasks', [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'due_date' => $task->due_date->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'title', 'status']]);
    }

    public function test_user_can_get_tasks()
    {
        $user = User::factory()->create();
        Task::factory(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_update_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $statuses = ['pending', 'in_progress', 'completed'];
        $newStatus = fake()->randomElement($statuses);
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/tasks/{$task->id}", [
            'status' => $newStatus,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', $newStatus);
    }

    public function test_user_cannot_update_other_users_task()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);
        $statuses = ['pending', 'in_progress', 'completed'];
        $newStatus = fake()->randomElement($statuses);
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/tasks/{$task->id}", [
            'status' => $newStatus,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully']);
    }

    public function test_user_can_filter_tasks_by_status()
    {
        $user = User::factory()->create();
        Task::factory(2)->create(['user_id' => $user->id, 'status' => 'pending']);
        Task::factory(3)->create(['user_id' => $user->id, 'status' => 'completed']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tasks?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
