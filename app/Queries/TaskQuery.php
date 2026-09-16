<?php

namespace App\Queries;

use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskQuery
{
    public function paginateForUser(User $user, array $filters): LengthAwarePaginator
    {
        return Task::query()
            ->with('categories')
            ->ownedBy($user)
            ->when(isset($filters['status']), fn ($q) => $q->ofStatus($filters['status']))
            ->when(
                isset($filters['category_id']),
                fn ($q) => $q->whereHas('categories', fn ($query) => $query->where('categories.id', $filters['category_id']))
            )
            ->when(
                isset($filters['search']),
                fn ($q) => $q->where(
                    fn ($query) => $query->where('title', 'like', '%'.$filters['search'].'%')
                        ->orWhere('description', 'like', '%'.$filters['search'].'%')
                )
            )
            ->when(isset($filters['sort_by']), function ($q) use ($filters) {
                $direction = $filters['sort_direction'] ?? 'desc';
                $q->orderBy($filters['sort_by'], $direction);
            }, fn ($q) => $q->latest())
            ->paginate($filters['per_page'] ?? 15);
    }
}
