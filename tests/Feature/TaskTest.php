<?php

namespace Tests\Feature;

use App\Actions\CreateTaskAction;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

    public function test_status_must_be_a_valid_task_status()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $createResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Invalid status test',
                'status' => 'invalid-status',
            ]);

        $createResponse->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $updateResponse = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/tasks/{$task->id}", [
                'status' => 'invalid-status',
            ]);

        $updateResponse->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $indexResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks?status=invalid-status');

        $indexResponse->assertStatus(422)
            ->assertJsonValidationErrors('status');
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
                'user_id' => $userB->id,
            ]);

        $response->assertStatus(200);

        $task->refresh();
        $this->assertEquals($userA->id, $task->user_id);
    }

    public function test_authenticated_user_is_authorized_to_create_tasks()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum');

        $this->assertTrue(Gate::forUser($user)->allows('create', Task::class));
    }

    // =========================================================================
    // اختبارات الفلاتر الجديدة للـ Index التي تم نقلها لـ TaskQuery
    // =========================================================================

    public function test_index_can_filter_by_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $taskWithCategory = Task::factory()->create(['user_id' => $user->id]);
        $taskWithCategory->categories()->attach($category);

        Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/tasks?category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $taskWithCategory->id);
    }

    public function test_index_can_search_by_title()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Learn Laravel 11']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Read a book']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?search=Laravel');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Learn Laravel 11');
    }

    public function test_index_can_search_by_description()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'description' => 'Fix the bug in the system']);
        Task::factory()->create(['user_id' => $user->id, 'description' => 'Write documentation']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?search=bug');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Fix the bug in the system');
    }

    public function test_index_respects_sort_by_and_direction()
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'B Task']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'A Task']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?sort_by=title&sort_direction=asc');

        $response->assertStatus(200);
        $this->assertEquals('A Task', $response->json('data.0.title'));
    }

    public function test_index_respects_pagination_per_page()
    {
        $user = User::factory()->create();
        Task::factory()->count(10)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        // التعديل هنا: فصلناها واستخدمنا $this بدلاً من $response
        $this->assertNotNull($response->json('meta.current_page'));
    }

    public function test_index_maintains_api_contract_wrapper()
    {
        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Contract test task',
            'description' => 'Contract test description',
            'status' => 'pending',
            'due_date' => '2026-09-24',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                    ],
                ],
                'links',
                'meta',
            ]);

        $data = $response->json('data.0');

        $this->assertIsInt($data['id']);
        $this->assertIsString($data['title']);
        $this->assertIsString($data['description']);
        $this->assertIsString($data['status']);
        $this->assertIsString($data['due_date']);
    }

    public function test_it_filters_tasks_by_status_search_and_category_together()
    {
        $user = User::factory()->create();

        $categoryX = Category::factory()->create();
        $categoryY = Category::factory()->create();

        // Matching Task: search + status + category
        $matchingTask = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'fix the database connection',
            'status' => 'pending',
        ]);
        $matchingTask->categories()->attach($categoryX);

        // Decoy 1: fails on status
        $decoy1 = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'fix the UI bug',
            'status' => 'completed',
        ]);
        $decoy1->categories()->attach($categoryX);

        // Decoy 2: fails on search
        $decoy2 = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'update documentation',
            'status' => 'pending',
        ]);
        $decoy2->categories()->attach($categoryX);

        // Decoy 3: fails on category
        $decoy3 = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'fix the server config',
            'status' => 'pending',
        ]);
        $decoy3->categories()->attach($categoryY);

        $response = $this->actingAs($user, 'sanctum')->getJson(
            "/api/v1/tasks?status=pending&search=fix&category_id={$categoryX->id}"
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingTask->id);
    }
}
