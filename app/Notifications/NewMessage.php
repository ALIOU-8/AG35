<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Message reçu alors que l'application est fermée : uniquement en push, la messagerie a son propre badge.
 */
class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return MemberNotification::pushEnabledFor($notifiable) ? [WebPushChannel::class] : [];
    }

    public function toWebPush(User $notifiable): WebPushMessage
    {
        $conversation = $this->message->conversation;
        $author = $this->message->user;

        return (new WebPushMessage)
            ->title($conversation->isDirect() ? $author->name : $author->name.' · '.($conversation->name ?? 'Groupe'))
            ->body($this->message->body ?: '📎 Pièce jointe')
            ->icon($author->avatarUrl() ?? '/icons/icon-192.png')
            ->badge('/icons/icon-192.png')
            ->tag('conversation-'.$conversation->uuid)
            ->renotify()
            ->data(['url' => route('conversations.show', $conversation)]);
    }
}
