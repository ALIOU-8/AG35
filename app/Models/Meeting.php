<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Meeting extends Model
{
    use EncryptsRouteKey, HasFactory;

    protected $fillable = ['title', 'description', 'scheduled_at', 'ended_at'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Meeting $meeting) {
            $meeting->uuid ??= (string) Str::uuid();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEnded(): bool
    {
        return $this->ended_at !== null;
    }

    public function status(): string
    {
        return match (true) {
            $this->isEnded() => 'ended',
            $this->scheduled_at?->isFuture() ?? false => 'scheduled',
            default => 'open',
        };
    }

    public function canBeManagedBy(User $user): bool
    {
        return $this->created_by === $user->id || $user->canManageMembers();
    }
}
