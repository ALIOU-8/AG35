<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = ['body', 'attachment_path', 'attachment_name', 'attachment_mime'];

    protected static function booted(): void
    {
        static::creating(function (Message $message) {
            $message->uuid ??= (string) Str::uuid();
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Forme envoyée au navigateur (page, temps réel, polling) : uuid uniquement, aucun id.
     *
     * @return array<string, mixed>
     */
    public function toChatArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'conversation' => $this->conversation->uuid,
            'body' => $this->body,
            'author' => $this->user?->toPublicArray() ?? ['uuid' => null, 'name' => 'Ancien membre', 'initials' => '?', 'avatar' => null],
            'time' => $this->created_at->format('H:i'),
            'day' => $this->created_at->translatedFormat('l j F Y'),
            'attachment' => $this->attachment_path ? [
                'url' => Storage::disk('public')->url($this->attachment_path),
                'name' => $this->attachment_name,
                'isImage' => Str::startsWith($this->attachment_mime, 'image/'),
            ] : null,
        ];
    }
}
