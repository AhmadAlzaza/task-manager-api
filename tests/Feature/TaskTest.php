<?php

namespace Tests\Feature;

use App\Actions\CreateTaskAction;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', [
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

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_update_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $statuses = ['pending', 'in_progress', 'completed'];
        $newStatus = fake()->randomElement($statuses);
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", [
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
        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", [
            'status' => $newStatus,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_other_users_task()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);
        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task deleted successfully']);
    }

    public function test_user_can_filter_tasks_by_status()
    {
        $user = User::factory()->create();
        Task::factory(2)->create(['user_id' => $user->id, 'status' => 'pending']);
        Task::factory(3)->create(['user_id' => $user->id, 'status' => 'completed']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_task()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $task->id]);
    }

    public function test_user_cannot_view_other_users_task()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_create_task_with_categories()
    {
        $user = User::factory()->create();
        $categories = Category::factory(2)->create();
        $task = Task::factory()->make();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tasks', [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'due_date' => $task->due_date->format('Y-m-d'),
            'categories' => $categories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.categories');
    }

    public function test_user_can_update_task_categories()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $oldCategory = Category::factory()->create();
        $task->categories()->attach($oldCategory);

        $newCategories = Category::factory(2)->create();

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/v1/tasks/{$task->id}", [
            'categories' => $newCategories->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.categories');

        $this->assertDatabaseMissing('category_task', [
            'task_id' => $task->id,
            'category_id' => $oldCategory->id,
        ]);
    }

    public function test_task_creation_rolls_back_if_category_attach_fails()
    {
        $user = User::factory()->create();
        $task = Task::factory()->make();

        try {
            (new CreateTaskAction)->execute([
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'due_date' => $task->due_date,
            ], $user, [999999]);
        } catch (\Throwable) {
            // متوقع
        }

        $this->assertDatabaseMissing('tasks', ['title' => $task->title]);
    }
    public function test_user_cannot_assign_task_to_another_user_via_create_payload()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // محاولة إنشاء task باسم userB
        $response = $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Attempt Hack',
                'user_id' => $userB->id,
            ]);

        $response->assertStatus(201);

        $task = Task::latest()->first();

        $this->assertEquals($userA->id, $task->user_id);
        $this->assertNotEquals($userB->id, $task->user_id);
    }

    public function test_user_cannot_change_task_ownership_via_update_payload()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $userA->id]);


        $response = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'user_id' => $userB->id,  // ← محاولة نقل الملكية
            ]);

        $response->assertStatus(200);

        $task->refresh();
        // الملكية يجب ألا تتغير
        $this->assertEquals($userA->id, $task->user_id);
    }
}
