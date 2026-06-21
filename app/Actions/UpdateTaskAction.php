<?php

namespace App\Actions;

use App\Models\Task;

class UpdateTaskAction
{

    public function execute(array $data, Task $task, array $categories = [])
    {
        $task->update($data);
        if ($categories) {
            $task->categories()->sync($categories);
        }
        $task->load('user', 'categories');
        return $task;
    }
}
