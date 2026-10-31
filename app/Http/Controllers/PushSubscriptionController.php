<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    /**
     * Services push des navigateurs. Le serveur envoie des requêtes à l'adresse enregistrée :
     * on refuse toute autre destination (pas de requête vers le réseau interne).
     */
    public const PUSH_SERVICE_HOSTS = [
        'fcm.googleapis.com',
        'updates.push.services.mozilla.com',
        'web.push.apple.com',
        '.push.apple.com',
        '.notify.windows.com',
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH, function (string $attribute, mixed $value, Closure $fail) {
                if (! self::isPushService((string) parse_url((string) $value, PHP_URL_HOST))) {
                    $fail('Service de notifications non reconnu.');
                }
            }],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'in:aesgcm,aes128gcm'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm',
        );

        return response()->json(['subscribed' => true], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string']]);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['subscribed' => false]);
    }

    protected static function isPushService(string $host): bool
    {
        foreach (self::PUSH_SERVICE_HOSTS as $allowed) {
            if ($host === $allowed || (Str::startsWith($allowed, '.') && Str::endsWith($host, $allowed))) {
                return true;
            }
        }

        return false;
    }
}
