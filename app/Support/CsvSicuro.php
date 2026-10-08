<?php

namespace App\Support;

/**
 * Valori da scrivere in CSV/Excel: una cella che inizia con `= + - @` (o tab/ritorno a capo) verrebbe interpretata come
 * formula da Excel ("CSV injection"); si antepone un apostrofo. Nomi e altri testi arrivano dal pubblico.
 */
final class CsvSicuro
{
    public static function cella(mixed $valore): string
    {
        $valore = (string) $valore;

        return $valore !== '' && in_array($valore[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$valore : $valore;
    }
}
