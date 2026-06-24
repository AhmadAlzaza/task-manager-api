<?php

namespace App\Models;

use App\Policies\TaskPolicy;
use GuzzleHttp\Psr7\Query;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Builder;

#[UsePolicy(TaskPolicy::class)]
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
        'user_id',
    ];
    public function scopeOwnedBy(Builder $query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
    public function scopeOfStatus(Builder $query, string $status)
    {
        return $query->where('status', $status);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
