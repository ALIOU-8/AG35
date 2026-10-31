<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable([
    'name', 'email', 'password', 'phone', 'school', 'field_of_study', 'study_level',
    'origin_city', 'arrival_year', 'bio',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use EncryptsRouteKey, HasFactory, HasPushSubscriptions, Notifiable;

    public const ROLES = [
        'admin' => 'Administrateur',
        'bureau' => 'Membre du bureau',
        'membre' => 'Membre',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'active' => 'Actif',
        'suspended' => 'Suspendu',
    ];

    public const STUDY_LEVELS = ['L1', 'L2', 'L3', 'BUT / BTS', 'M1', 'M2', 'Doctorat', 'École d\'ingénieur', 'Autre', 'Diplômé(e)'];

    /**
     * Mêmes valeurs par défaut que la base : un compte tout juste créé a déjà un rôle et un statut en mémoire.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'role' => 'membre',
        'status' => 'pending',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'arrival_year' => 'integer',
            'bureau_order' => 'integer',
            'password' => 'hashed',
        ];
    }

    /**
     * Les canaux de présence exposent cet identifiant aux autres membres : jamais l'id.
     */
    public function getAuthIdentifierForBroadcasting(): string
    {
        return $this->uuid;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)->withPivot('last_read_at')->withTimestamps();
    }

    public function membershipFees(): HasMany
    {
        return $this->hasMany(MembershipFee::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canManageMembers(): bool
    {
        return in_array($this->role, ['admin', 'bureau'], true);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->implode('');
    }

    /**
     * Situation de la cotisation d'une saison : montant dû, déjà payé, reste à régler et statut.
     *
     * @return array{season: string, due: float, paid: float, remaining: float, status: string}
     */
    public function duesFor(string $season): array
    {
        $due = MembershipFee::dueAmount();
        $paid = (float) $this->membershipFees()->where('season', $season)->sum('amount');

        return [
            'season' => $season,
            'due' => $due,
            'paid' => $paid,
            'remaining' => max(0, $due - $paid),
            'status' => MembershipFee::paymentStatus($paid, $due),
        ];
    }

    public function studies(): string
    {
        return collect([$this->field_of_study, $this->study_level])->filter()->implode(' · ');
    }

    /**
     * Données exposées au navigateur (chat, visio) : pas d'id, seulement l'uuid public.
     *
     * @return array<string, string|null>
     */
    public function toPublicArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'initials' => $this->initials(),
            'avatar' => $this->avatarUrl(),
        ];
    }
}
