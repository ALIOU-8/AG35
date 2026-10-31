<?php

use App\Models\Conversation;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Les noms de canaux utilisent des uuid publics : aucun id de base de données côté navigateur.

Broadcast::channel('user.{uuid}', function (User $user, string $uuid) {
    return $user->isActive() && $user->uuid === $uuid;
});

Broadcast::channel('conversation.{uuid}', function (User $user, string $uuid) {
    return $user->isActive() && Conversation::where('uuid', $uuid)
        ->whereHas('participants', fn ($query) => $query->whereKey($user->id))
        ->exists();
});

Broadcast::channel('meeting.{uuid}', function (User $user, string $uuid) {
    $meeting = Meeting::where('uuid', $uuid)->first();

    if (! $user->isActive() || ! $meeting || $meeting->isEnded()) {
        return false;
    }

    return $user->toPublicArray();
});
