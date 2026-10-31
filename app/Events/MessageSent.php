<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public Message $message) {}

    /**
     * Un canal par participant : chaque membre reçoit ses messages sur toutes les pages
     * (badge de navigation, liste des conversations, conversation ouverte).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return $this->message->conversation->participants
            ->map(fn ($user) => new PrivateChannel('user.'.$user->uuid))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message->toChatArray(),
            'url' => route('conversations.show', $this->message->conversation),
            'type' => $this->message->conversation->type,
            'conversationName' => $this->message->conversation->isDirect()
                ? $this->message->user?->name
                : $this->message->conversation->name,
        ];
    }
}
