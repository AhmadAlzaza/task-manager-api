<?php

namespace App\Http\Controllers;

use App\Actions\CreateTaskAction;
use App\Actions\UpdateTaskAction;
use App\Http\Requests\IndexTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Queries\TaskQuery;
use App\Traits\ApiResponse;

/**
 * @group Tasks
 */
class TaskController extends Controller
{
    use ApiResponse;

    public function index(IndexTaskRequest $request, TaskQuery $query)
    {
        $tasks = $query->paginateForUser(
            $request->user(),
            $request->validated()
        );

        return $this->resourceResponse(TaskResource::collection($tasks), 'Tasks retrieved successfully');
    }

    /**
     * @bodyParam categories integer[]
     */
    public function store(StoreTaskRequest $request, CreateTaskAction $action)
    {
        $this->authorize('create', Task::class);

        $task = $action->execute(
            $request->safe()->except(['categories']),
            $request->user(),
            $request->input('categories', [])
        );

        return $this->resourceResponse(new TaskResource($task), 'Task created successfully', 201);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $update)
    {
        $this->authorize('update', $task);

        $updatedTask = $update->execute(
            $request->safe()->except(['categories']),
            $task,
            $request->has('categories') ? $request->input('categories', []) : null
        );

        return $this->resourceResponse(new TaskResource($updatedTask), 'Task updated successfully');
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);
        $task->load('user', 'categories');

        return $this->resourceResponse(new TaskResource($task), 'Task retrieved successfully');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();

        return $this->successResponse(null, 'Task deleted successfully');
    }
}
