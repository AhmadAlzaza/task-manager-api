<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Resources\TaskResource;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;
use App\Actions\CreateTaskAction;
use App\Actions\UpdateTaskAction;

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
        $newtask = $update->execute($request->validated(), $task, $request->input('categories', []));

        return new TaskResource($newtask);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully']);
    }
}
