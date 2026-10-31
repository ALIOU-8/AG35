<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;

/**
 * Les ids ne sortent jamais en clair : route('...', $model) chiffre la clé,
 * et la liaison de route la déchiffre (404 si la valeur est altérée).
 */
trait EncryptsRouteKey
{
    public function getRouteKey(): string
    {
        return encrypt($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::findByEncryptedKey($value);
    }

    public static function findByEncryptedKey(?string $value): ?static
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $id = decrypt($value);
        } catch (DecryptException) {
            return null;
        }

        return is_scalar($id) ? static::query()->find($id) : null;
    }
}
