<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipFee extends Model
{
    use EncryptsRouteKey;

    public const METHODS = ['Espèces', 'Virement', 'Lydia / Wero', 'Chèque', 'Autre'];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Impayée',
        'partial' => 'Partielle',
        'paid' => 'À jour',
    ];

    protected $fillable = ['season', 'amount', 'paid_on', 'method', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_on' => 'date',
        ];
    }

    /**
     * Saison universitaire en cours : de septembre à août.
     */
    public static function currentSeason(): string
    {
        $start = now()->month >= 9 ? now()->year : now()->year - 1;

        return $start.'-'.($start + 1);
    }

    /**
     * Montant demandé à chaque membre actif pour une saison (« Cotisation annuelle » du contenu du site).
     */
    public static function dueAmount(): float
    {
        return max(0, (float) str_replace(',', '.', (string) Setting::get('membership_fee')));
    }

    /**
     * À jour dès que les paiements de la saison couvrent le montant dû ; partielle s'il manque une part.
     */
    public static function paymentStatus(float $paid, float $due): string
    {
        return match (true) {
            $paid >= $due => 'paid',
            $paid > 0 => 'partial',
            default => 'unpaid',
        };
    }

    /**
     * Saisons consultables : la saison en cours et celles qui ont des paiements, la plus récente d'abord.
     *
     * @return array<int, string>
     */
    public static function seasons(): array
    {
        return static::query()->distinct()->pluck('season')
            ->push(static::currentSeason())
            ->unique()->sortDesc()->values()->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
