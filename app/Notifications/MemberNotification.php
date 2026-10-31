<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Notification de l'espace membres, façon Facebook : enregistrée tout de suite pour la cloche,
 * puis envoyée en push (file d'attente) sur les appareils où le membre a activé les notifications.
 */
abstract class MemberNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $actor) {}

    /**
     * Type affiché par l'icône : like, comment ou meeting.
     */
    abstract public function kind(): string;

    /**
     * Phrase qui suit le nom de l'auteur, par exemple « aime votre publication ».
     */
    abstract public function text(User $notifiable): string;

    abstract public function excerpt(): ?string;

    abstract public function url(): string;

    /**
     * Références internes, jamais envoyées au navigateur : elles permettent de retirer
     * la notification quand son objet disparaît.
     *
     * @return array<string, int|string>
     */
    abstract public function references(): array;

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return self::pushEnabledFor($notifiable) ? ['database', WebPushChannel::class] : ['database'];
    }

    /**
     * La cloche ne doit pas attendre un worker : seule la notification push passe par la file d'attente.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'actor' => $this->actor->toPublicArray(),
            'text' => $this->text($notifiable),
            'excerpt' => $this->excerpt(),
            'url' => $this->url(),
            ...$this->references(),
        ];
    }

    public function toWebPush(User $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->actor->name.' '.$this->text($notifiable))
            ->body((string) $this->excerpt())
            ->icon($this->actor->avatarUrl() ?? '/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag('notification-'.$this->id)
            ->data(['url' => route('notifications.open', $this->id)]);
    }

    public static function pushConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
    }

    public static function pushEnabledFor(User $notifiable): bool
    {
        return self::pushConfigured() && $notifiable->pushSubscriptions()->exists();
    }

    /**
     * Retire les notifications liées à un objet supprimé ou annulé (publication, commentaire, « j'aime »…).
     */
    public static function forget(string $reference, int|string $value): void
    {
        DatabaseNotification::query()->where("data->{$reference}", $value)->delete();
    }
}
