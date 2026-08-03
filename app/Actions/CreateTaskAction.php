<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function execute(array $data, User $user, array $categories = []): Task
    {
        return DB::transaction(function () use ($data, $user, $categories) {
            $task = Task::create([
                ...$data,
                'user_id' => $user->id,
            ]);

            if ($categories) {
                $task->categories()->attach($categories);
            }

            $task->load('user', 'categories');

            return $task;
        });
    }
}
