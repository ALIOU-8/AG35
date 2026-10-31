<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use EncryptsRouteKey;

    protected $fillable = ['body'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id || $user->canManageMembers();
    }

    /**
     * @return array<string, mixed>
     */
    public function toFeedArray(User $viewer): array
    {
        return [
            'author' => $this->user->toPublicArray() + ['url' => route('members.show', $this->user)],
            'body' => $this->body,
            'date' => $this->created_at->diffForHumans(),
            'deleteUrl' => $this->canBeDeletedBy($viewer) ? route('comments.destroy', $this) : null,
        ];
    }
}
