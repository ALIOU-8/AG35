<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Textes du site public, modifiables depuis l'administration.
     */
    public const DEFAULTS = [
        'association_name' => 'AG35 — Association des Étudiants Guinéens de Rennes',
        'slogan' => 'Solidarité, entraide et réussite pour les étudiants guinéens à Rennes.',
        'about' => "L'AG35 rassemble les étudiantes et étudiants guinéens de Rennes et d'Ille-et-Vilaine. Nous accueillons les nouveaux arrivants, nous les accompagnons dans leurs démarches (logement, titre de séjour, inscription, CAF…) et nous faisons vivre la culture guinéenne à travers des rencontres, des événements et des projets solidaires.",
        'mission_1' => 'Accueillir et orienter les nouveaux étudiants dès leur arrivée à Rennes.',
        'mission_2' => "Créer un réseau d'entraide : logement, stages, emplois, révisions.",
        'mission_3' => 'Promouvoir la culture guinéenne et le dialogue entre les communautés.',
        'contact_email' => 'contact@ag35.fr',
        'contact_phone' => '',
        'address' => 'Rennes, Ille-et-Vilaine (35)',
        'facebook_url' => '',
        'instagram_url' => '',
        'whatsapp_url' => '',
        'membership_fee' => '20',
        'dues_instructions' => "Réglez votre cotisation auprès du trésorier : en espèces lors d'une réunion, par virement ou par Lydia / Wero. Indiquez votre nom et la saison en référence.",
    ];

    public static function get(string $key): ?string
    {
        return self::values()[$key] ?? null;
    }

    /**
     * Tous les textes du site, valeur enregistrée sinon valeur par défaut.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        $stored = Cache::rememberForever('settings', fn () => static::query()->pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }
}
