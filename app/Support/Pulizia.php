<?php

namespace App\Support;

/**
 * Pulizia dei dati anagrafici inseriti nei moduli, applicata prima della validazione e quindi anche a ciò che finisce
 * nel database: così i dati sono sempre uniformi.
 * - codice fiscale, codice destinatario: maiuscolo, senza spazi; sigla provincia: maiuscola;
 * - nome, cognome, via, comune: **solo la prima lettera di ogni parola maiuscola** (dopo spazio, trattino, apostrofo, punto,
 *   virgola, barra: "d'angelo" → "D'Angelo", "12/a" → "12/A", "MONTE san savino" → "Monte San Savino");
 * - email e PEC: minuscole; telefono/CAP/P.IVA: spazi ripuliti. Spazi doppi e ai bordi tolti ovunque.
 * ⚠️ Non tocca la ragione sociale (sigle e marchi: "CRI", "S.r.l.", "ACME") né il testo libero.
 */
final class Pulizia
{
    public static function spazi(?string $valore): ?string
    {
        return $valore === null ? null : trim(preg_replace('/\s+/u', ' ', $valore));
    }

    public static function maiuscolo(?string $valore): ?string
    {
        return $valore === null ? null : mb_strtoupper(preg_replace('/\s+/u', '', $valore));
    }

    public static function minuscolo(?string $valore): ?string
    {
        return $valore === null ? null : mb_strtolower(preg_replace('/\s+/u', '', $valore));
    }

    /** Prima lettera di ogni parola maiuscola, il resto minuscolo. */
    public static function titolo(?string $valore): ?string
    {
        $valore = self::spazi($valore);
        if ($valore === null || $valore === '') {
            return $valore;
        }

        return preg_replace_callback('/(^|[\s\-\'’.,\/])(\p{L})/u', fn ($m) => $m[1].mb_strtoupper($m[2]), mb_strtolower($valore));
    }

    /** Dati di una persona (nominativo/referente): ripulisce solo le chiavi presenti, lascia le altre. */
    public static function persona(array $d): array
    {
        return self::applica($d, [
            'nome' => 'titolo', 'cognome' => 'titolo', 'via' => 'titolo', 'comune' => 'titolo',
            'email' => 'minuscolo', 'codice_fiscale' => 'maiuscolo', 'provincia' => 'maiuscolo',
            'telefono' => 'spazi', 'cap' => 'spazi',
        ]);
    }

    /** Dati di fatturazione (privato o azienda). */
    public static function fatturazione(array $d): array
    {
        return self::applica($d, [
            'nome' => 'titolo', 'cognome' => 'titolo', 'via' => 'titolo', 'comune' => 'titolo',
            'ragione_sociale' => 'spazi', 'partita_iva' => 'maiuscolo', 'codice_fiscale' => 'maiuscolo',
            'provincia' => 'maiuscolo', 'cap' => 'spazi', 'codice_destinatario' => 'maiuscolo', 'pec' => 'minuscolo',
        ]);
    }

    private static function applica(array $d, array $campi): array
    {
        foreach ($campi as $chiave => $metodo) {
            if (array_key_exists($chiave, $d) && is_string($d[$chiave])) {
                $d[$chiave] = self::$metodo($d[$chiave]);
            }
        }

        return $d;
    }
}
