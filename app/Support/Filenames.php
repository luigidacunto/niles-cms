<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Nome file leggibile per lo storage: niente stringhe random illeggibili.
 * I file devono avere nomi sensati sia quando linkati/scaricati sia nelle URL dirette
 * `/storage/...` (immagini). La base viene dal form che carica il file (titolo documento,
 * nome+ruolo persona, ...); si aggiunge data/ora + 4 char casuali per l'unicità.
 */
class Filenames
{
    public static function readable(string $base, string $ext): string
    {
        $slug = Str::slug($base);
        $slug = $slug !== '' ? Str::limit($slug, 80, '') : 'file';
        $ext = Str::lower(preg_replace('/[^a-z0-9]/i', '', $ext)) ?: 'bin';

        return $slug.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(4)).'.'.$ext;
    }
}
