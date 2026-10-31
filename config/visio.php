<?php

return [

    /*
    | webrtc : visio intégrée (pair-à-pair, signalisation via Reverb), idéale jusqu'à ~6-8 personnes.
    | jitsi  : salle Jitsi Meet intégrée dans la page, pour les grandes réunions (assemblées générales).
    */
    'driver' => env('VISIO_DRIVER', 'webrtc'),

    'jitsi_domain' => env('VISIO_JITSI_DOMAIN', 'meet.jit.si'),

    /*
    | Serveurs ICE. Un serveur TURN est fortement recommandé en production : sans lui,
    | certains réseaux (4G, Wi-Fi universitaires) ne permettent pas la connexion directe.
    */
    'ice_servers' => array_values(array_filter([
        ['urls' => array_map('trim', explode(',', env('VISIO_STUN_URLS', 'stun:stun.l.google.com:19302,stun:stun1.l.google.com:19302')))],
        env('VISIO_TURN_URL') ? [
            'urls' => array_map('trim', explode(',', env('VISIO_TURN_URL'))),
            'username' => env('VISIO_TURN_USERNAME'),
            'credential' => env('VISIO_TURN_CREDENTIAL'),
        ] : null,
    ])),

];
