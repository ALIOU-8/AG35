<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use EncryptsRouteKey, HasFactory;

    protected $fillable = ['type', 'name', 'created_by', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            $conversation->uuid ??= (string) Str::uuid();
        });
    }

    public static function general(): self
    {
        return static::firstOrCreate(['type' => 'general'], ['name' => 'Général']);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('last_read_at')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function otherParticipant(User $viewer): ?User
    {
        return $this->participants->firstWhere('id', '!=', $viewer->id);
    }

    public function displayName(User $viewer): string
    {
        if ($this->isDirect()) {
            return $this->otherParticipant($viewer)?->name ?? 'Ancien membre';
        }

        return $this->name ?? 'Groupe';
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }

    public function unreadCountFor(User $user): int
    {
        $lastRead = $this->participants->firstWhere('id', $user->id)?->pivot?->last_read_at;

        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->when($lastRead, fn ($query) => $query->where('created_at', '>', $lastRead))
            ->count();
    }
}
