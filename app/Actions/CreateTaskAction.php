<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Task;

class CreateTaskAction
{

    public function execute(array $data, User $user, array $categories = [])
    {
        $task = Task::create([
            ...$data,
            'user_id' => $user->id,
        ]);
        if ($categories) {
            $task->categories()->attach($categories);
        }
        $task->load('user', 'categories');
        return $task;
    }
}
