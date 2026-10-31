<?php

namespace App\Models;

use App\Models\Concerns\EncryptsRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipFee extends Model
{
    use EncryptsRouteKey;

    public const METHODS = ['Espèces', 'Virement', 'Lydia / Wero', 'Chèque', 'Autre'];

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
