<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Codici OTP monouso via email, condivisi da admin e membri. Il modello che lo usa definisce la costante
 * OWNER_COLUMN (colonna FK verso il proprietario del codice, es. 'admin_id') e ha le colonne
 * code_hash/expires_at/consumed_at.
 */
trait IssuesLoginCodes
{
    /**
     * Invalida i codici ancora pendenti del proprietario ed emette il nuovo.
     * Ritorna il codice in chiaro (disponibile solo qui, alla generazione).
     */
    public static function issueFor(Model $owner): string
    {
        static::where(static::OWNER_COLUMN, $owner->getKey())->whereNull('consumed_at')->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        static::create([
            static::OWNER_COLUMN => $owner->getKey(),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public static function verifyFor(Model $owner, string $code): bool
    {
        $login = static::where(static::OWNER_COLUMN, $owner->getKey())
            ->whereNull('consumed_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (! $login || ! Hash::check($code, $login->code_hash)) {
            return false;
        }

        $login->update(['consumed_at' => now()]);

        return true;
    }
}
