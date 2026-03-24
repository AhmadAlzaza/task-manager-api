<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Resources\TaskResource;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::with('user', 'categories')
            ->where('user_id', $request->user()->id)
            ->when(
                in_array($request->status, ['pending', 'in_progress', 'completed']),
                fn($q) => $q->where('status', $request->status)
            )
            ->paginate(15);

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = Task::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        if ($request->categories) {
            $task->categories()->attach($request->categories);
        }

        $task->load('user', 'categories');

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $task->load('user', 'categories');

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        if ($request->has('categories')) {
            $task->categories()->sync($request->categories);
        }

        $task->load('user', 'categories');

        return new TaskResource($task);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
