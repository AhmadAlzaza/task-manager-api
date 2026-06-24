<?php

namespace App\Actions;

use App\Models\Task;

class UpdateTaskAction
{

    public function execute(array $data, Task $task, ?array $categories = null)
    {
        $task->update($data);

        if ($categories !== null) {
            $task->categories()->sync($categories);
        }

        $task->load('user', 'categories');
        return $task;
    }
}
