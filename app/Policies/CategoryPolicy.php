<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User; // 👈 استيراد الـ Enum

class CategoryPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, Category $category): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
