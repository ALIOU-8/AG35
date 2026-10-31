<?php

namespace App\Events;

use App\Models\User;
use App\Support\NotificationPresenter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\DatabaseNotification;

class NotificationCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public User $recipient, public DatabaseNotification $notification) {}

    /**
     * Même canal privé que la messagerie : le destinataire la reçoit sur toutes les pages.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->recipient->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => NotificationPresenter::present($this->notification),
            'unread' => $this->recipient->unreadNotifications()->count(),
        ];
    }
}
