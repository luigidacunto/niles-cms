<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Disambigua uno slug che altrimenti andrebbe in conflitto (es. un evento annuale ricorrente che riusa lo
 * stesso titolo). Ordine dei tentativi, ciascuno provato solo se il precedente è occupato:
 *   1. lo slug semplice
 *   2. slug + data di creazione (gg-mm-aaaa), solo se si passa `$date`: più utile per la SEO di un numero
 *      e si legge come «l'edizione di quest'anno» per i contenuti ricorrenti
 *   3. slug + data + -2, -3, ... (raggiungibile solo se lo stesso slug è stato riusato nello stesso giorno),
 *      oppure, se non è stata passata nessuna data, slug + -2, -3, ...
 * Lo usa qualunque modello con una colonna `slug` (risolta con `static::`): oggi Post e Page.
 */
trait HasUniqueSlug
{
    public static function uniqueSlug(string $base, ?int $ignoreId = null, ?Carbon $date = null): string
    {
        if (! static::slugTaken($base, $ignoreId)) {
            return $base;
        }

        if ($date) {
            $withDate = $base.'-'.$date->format('d-m-Y');
            if (! static::slugTaken($withDate, $ignoreId)) {
                return $withDate;
            }
            $base = $withDate;
        }

        $slug = $base;
        $i = 2;

        while (static::slugTaken($slug, $ignoreId)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private static function slugTaken(string $slug, ?int $ignoreId): bool
    {
        return static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists();
    }
}
