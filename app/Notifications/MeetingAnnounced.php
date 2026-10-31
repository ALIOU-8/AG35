<?php

namespace App\Notifications;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Str;

class MeetingAnnounced extends MemberNotification
{
    public function __construct(public Meeting $meeting)
    {
        parent::__construct($meeting->creator);
    }

    public function kind(): string
    {
        return 'meeting';
    }

    public function text(User $notifiable): string
    {
        return $this->meeting->scheduled_at
            ? 'a planifié une réunion : '.$this->meeting->title
            : 'a lancé une réunion en visio : '.$this->meeting->title;
    }

    public function excerpt(): ?string
    {
        return $this->meeting->scheduled_at
            ? Str::ucfirst($this->meeting->scheduled_at->translatedFormat('l j F à H:i'))
            : 'Rejoignez-la maintenant';
    }

    public function url(): string
    {
        return $this->meeting->scheduled_at ? route('meetings.index') : route('meetings.show', $this->meeting);
    }

    public function references(): array
    {
        return ['meeting_id' => $this->meeting->id];
    }
}
