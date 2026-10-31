<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use EncryptsRouteKey, HasFactory;

    protected $fillable = ['body', 'visibility'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('position');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
    }

    public function scopePublic(Builder $query): void
    {
        $query->where('visibility', 'public');
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * Titre affiché sur le site public : la première ligne du texte.
     */
    public function headline(): string
    {
        $firstLine = trim(Str::before(trim((string) $this->body), "\n"));

        return $firstLine !== '' ? Str::limit($firstLine, 90) : 'Actualité du '.$this->created_at->translatedFormat('j F Y');
    }

    /**
     * Court extrait cité dans les notifications.
     */
    public function excerpt(int $limit = 80): ?string
    {
        if (filled($this->body)) {
            return Str::limit(Str::squish($this->body), $limit);
        }

        return $this->media()->exists() ? 'Photo ou vidéo' : null;
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->user_id === $user->id || $user->canManageMembers();
    }
}
