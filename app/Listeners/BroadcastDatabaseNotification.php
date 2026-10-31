<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Models\User;
use App\Support\Realtime;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

class BroadcastDatabaseNotification
{
    /**
     * Chaque notification enregistrée pour la cloche est relayée en direct à son destinataire.
     */
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database' || ! $event->response instanceof DatabaseNotification || ! $event->notifiable instanceof User) {
            return;
        }

        Realtime::toOthers(new NotificationCreated($event->notifiable, $event->response));
    }
}
