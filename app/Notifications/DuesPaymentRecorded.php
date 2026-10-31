<?php

namespace App\Notifications;

use App\Models\MembershipFee;
use App\Models\User;

/**
 * Reçu envoyé au membre quand le bureau enregistre son paiement.
 */
class DuesPaymentRecorded extends MemberNotification
{
    public function __construct(public MembershipFee $fee)
    {
        parent::__construct($fee->recorder);
    }

    public function kind(): string
    {
        return 'dues';
    }

    public function text(User $notifiable): string
    {
        return 'a enregistré votre paiement de cotisation '.$this->fee->season;
    }

    public function excerpt(): ?string
    {
        return number_format((float) $this->fee->amount, 2, ',', ' ').' € · '.$this->fee->method;
    }

    public function url(): string
    {
        return route('dues.show');
    }

    public function references(): array
    {
        return ['fee_id' => $this->fee->id, 'season' => $this->fee->season];
    }
}
