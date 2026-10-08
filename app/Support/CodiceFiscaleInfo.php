<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Informazioni ricavabili dal codice fiscale (oggi solo la data di nascita, per il blocco dei
 * minorenni nei moduli online). Gestisce le omocodie (cifre sostituite da lettere LMNPQRSTUV) e il
 * secolo: l'anno è a 2 cifre, si sceglie il più recente che non cade nel futuro (chi ha 100+ anni
 * non è un caso reale di iscrizione). Controllo di struttura, non del carattere di controllo.
 */
class CodiceFiscaleInfo
{
    private const OMOCODIA = ['L' => 0, 'M' => 1, 'N' => 2, 'P' => 3, 'Q' => 4, 'R' => 5, 'S' => 6, 'T' => 7, 'U' => 8, 'V' => 9];

    private const MESI = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'H' => 6, 'L' => 7, 'M' => 8, 'P' => 9, 'R' => 10, 'S' => 11, 'T' => 12];

    /** Data di nascita, o null se il codice non ha una struttura/data valida. */
    public static function dataNascita(string $cf): ?Carbon
    {
        $cf = strtoupper(trim($cf));

        if (! preg_match('/^[A-Z]{6}([0-9LMNPQRSTUV]{2})([ABCDEHLMPRST])([0-9LMNPQRSTUV]{2})[A-Z][0-9LMNPQRSTUV]{3}[A-Z]$/', $cf, $m)) {
            return null;
        }

        $anno = (int) self::cifre($m[1]);
        $giorno = (int) self::cifre($m[3]);
        $giorno = $giorno > 40 ? $giorno - 40 : $giorno; // le donne hanno +40
        $mese = self::MESI[$m[2]];

        if (! checkdate($mese, $giorno, 2000 + $anno)) {
            return null;
        }

        $data = Carbon::create(2000 + $anno, $mese, $giorno)->startOfDay();

        return $data->isFuture() ? $data->subYears(100) : $data;
    }

    /** null se la data non è ricavabile. */
    public static function maggiorenne(string $cf): ?bool
    {
        $data = self::dataNascita($cf);

        return $data === null ? null : $data->lte(now()->subYears(18)->startOfDay());
    }

    private static function cifre(string $s): string
    {
        return implode('', array_map(fn ($c) => ctype_digit($c) ? $c : self::OMOCODIA[$c], str_split($s)));
    }
}
