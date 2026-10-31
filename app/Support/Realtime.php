<?php

namespace App\Support;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Broadcast;
use Throwable;

class Realtime
{
    /**
     * Diffuse l'événement aux autres connexions sans jamais faire échouer la requête :
     * si le serveur Reverb est arrêté, le chat bascule sur le polling côté navigateur.
     */
    public static function toOthers(ShouldBroadcast $event): void
    {
        try {
            if (method_exists($event, 'dontBroadcastToCurrentUser')) {
                $event->dontBroadcastToCurrentUser();
            }

            Broadcast::queue($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
