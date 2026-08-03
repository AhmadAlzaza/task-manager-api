<?php

namespace App\Actions;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    public function execute(array $data, Task $task, ?array $categories = null): Task
    {
        return DB::transaction(function () use ($data, $task, $categories) {
            $task->update($data);

            if ($categories !== null) {
                $task->categories()->sync($categories);
            }

            $task->load('user', 'categories');

            return $task;
        });
    }
}
