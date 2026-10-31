<?php

namespace App\Notifications;

use App\Models\User;

class DuesReminder extends MemberNotification
{
    public function __construct(User $actor, public string $season, public float $remaining)
    {
        parent::__construct($actor);
    }

    public function kind(): string
    {
        return 'dues';
    }

    public function text(User $notifiable): string
    {
        return 'vous rappelle votre cotisation '.$this->season;
    }

    public function excerpt(): ?string
    {
        return 'Reste à régler : '.number_format($this->remaining, 2, ',', ' ').' €';
    }

    public function url(): string
    {
        return route('dues.show');
    }

    public function references(): array
    {
        return ['season' => $this->season];
    }
}
