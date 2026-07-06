<?php

namespace App\Models;

use App\Policies\TaskPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\User $user
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $categories
 * @property-read int|null $categories_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static Builder<static>|Task newModelQuery()
 * @method static Builder<static>|Task newQuery()
 * @method static Builder<static>|Task ofStatus(string $status)
 * @method static Builder<static>|Task ownedBy(\App\Models\User $user)
 * @method static Builder<static>|Task query()
 * @method static Builder<static>|Task whereCreatedAt($value)
 * @method static Builder<static>|Task whereDescription($value)
 * @method static Builder<static>|Task whereDueDate($value)
 * @method static Builder<static>|Task whereId($value)
 * @method static Builder<static>|Task whereStatus($value)
 * @method static Builder<static>|Task whereTitle($value)
 * @method static Builder<static>|Task whereUpdatedAt($value)
 * @method static Builder<static>|Task whereUserId($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 */


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

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
        ];
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }
}
