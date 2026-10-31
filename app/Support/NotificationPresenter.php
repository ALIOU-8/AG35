<?php

namespace App\Support;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;

class NotificationPresenter
{
    /**
     * Forme envoyée au navigateur (cloche, page, temps réel) : les références internes restent côté serveur.
     *
     * @return array{id: string, kind: string, actor: array<string, string|null>, text: string, excerpt: string|null, url: string, time: string, read: bool}
     */
    public static function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'kind' => $data['kind'] ?? 'info',
            'actor' => Arr::only($data['actor'] ?? [], ['name', 'initials', 'avatar']),
            'text' => $data['text'] ?? '',
            'excerpt' => $data['excerpt'] ?? null,
            'url' => route('notifications.open', $notification->id),
            'time' => $notification->created_at->diffForHumans(),
            'read' => $notification->read_at !== null,
        ];
    }
}
