<?php

namespace App\Http\Controllers;

use App\Actions\CreateTaskAction;
use App\Actions\UpdateTaskAction;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Traits\ApiResponse; // 👈 1. استيراد الـ Trait
use Illuminate\Http\Request;

/**
 * @group Tasks
 */
class TaskController extends Controller
{
    use ApiResponse; // 👈 2. تفعيل الـ Trait

    public function index(IndexTaskRequest $request)
    {
        $tasks = Task::with('user', 'categories')
            ->ownedBy($request->user())
            ->when($request->input('status'), fn ($q) => $q->ofStatus($request->input('status')))
            ->paginate($request->input('per_page', 15));

        // 👇 استخدام دالة resourceResponse
        return $this->resourceResponse(TaskResource::collection($tasks), 'Tasks retrieved successfully');
    }

    /**
     * @bodyParam categories integer[] optional قائمة IDs للـ categories. Example: [1]
     */
    public function store(StoreTaskRequest $request, CreateTaskAction $action)
    {
        $this->authorize('create', Task::class);

        $task = $action->execute(
            // 👇 هنا السحر: نأخذ كل البيانات الموثقة ما عدا التصنيفات
            $request->safe()->except(['categories']),
            $request->user(),
            $request->input('categories', [])
        );

        return $this->resourceResponse(new TaskResource($task), 'Task created successfully', 201);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $update)
    {
        $this->authorize('update', $task);

        $newtask = $update->execute(
            // 👇 نفس الشيء هنا
            $request->safe()->except(['categories']),
            $task,
            $request->has('categories') ? $request->input('categories', []) : null
        );

        return $this->resourceResponse(new TaskResource($newtask), 'Task updated successfully');
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $task->load('user', 'categories');

        return $this->resourceResponse(new TaskResource($task), 'Task retrieved successfully');
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();

        // 👇 استخدام دالة successResponse لأننا لا نملك Resource هنا
        return $this->successResponse(null, 'Task deleted successfully');
    }
}
