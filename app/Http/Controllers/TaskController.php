<?php

namespace App\Http\Controllers;

use App\Actions\CreateTaskAction;
use App\Actions\UpdateTaskAction;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

/**
 * @group Tasks
 */
class TaskController extends Controller
{
    public function index(IndexTaskRequest $request)
    {
        $tasks = Task::with('user', 'categories')
            ->ownedBy($request->user())
            ->when($request->input('status'), fn ($q) => $q->ofStatus($request->input('status')))
            ->paginate($request->input('per_page', 15)); // استخدام per_page

        return TaskResource::collection($tasks);
    }

    /**
     * @bodyParam categories integer[] optional قائمة IDs للـ categories. Example: [1]
     */
    public function store(StoreTaskRequest $request, CreateTaskAction $action)
    {
        $task = $action->execute($request->validated(), $request->user(), $request->input('categories', []));

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $task->load('user', 'categories');

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $update)
    {
        $this->authorize('update', $task);

        $newtask = $update->execute(
            $request->validated(),
            $task,
            $request->has('categories') ? $request->input('categories', []) : null
        );

        return new TaskResource($newtask);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
