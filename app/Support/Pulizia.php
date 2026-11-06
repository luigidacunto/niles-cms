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

    /** Preposizioni e articoli che nei nomi di luogo restano minuscoli (tranne in prima posizione). */
    private const PARTICELLE = ['di', 'd', 'del', 'dello', 'della', 'dell', 'dei', 'degli', 'delle', 'da', 'dal', 'dallo', 'dalla', 'dall',
        'dai', 'dagli', 'dalle', 'in', 'nel', 'nello', 'nella', 'nell', 'nei', 'negli', 'nelle', 'su', 'sul', 'sullo', 'sulla', 'sull',
        'sui', 'sugli', 'sulle', 'a', 'al', 'allo', 'alla', 'all', 'ai', 'agli', 'alle', 'e', 'ed', 'con', 'per', 'tra', 'fra'];

    /**
     * Nome di luogo (via, comune): come titolo(), ma preposizioni e articoli dopo la prima parola restano minuscoli
     * («Via delle Imprese», «Reggio nell'Emilia», «San Giovanni in Fiore»). Non per nomi e cognomi («Di Maio», «De Luca»).
     */
    public static function luogo(?string $valore): ?string
    {
        $valore = self::titolo($valore);
        if ($valore === null || $valore === '') {
            return $valore;
        }

        // Numeri romani di almeno due lettere in maiuscolo («Via XX Settembre»), escluse le particelle (DI, DAI…).
        // ponytail: una parola che è anche un numero romano valido («Mix», «Vi») diventa maiuscola; elenco di eccezioni se capita.
        $valore = preg_replace_callback('/\b(\p{L}{2,})\b/u', function ($m) {
            $p = mb_strtolower($m[1]);

            return ! in_array($p, self::PARTICELLE, true) && preg_match('/^m{0,3}(c[md]|d?c{0,3})(x[cl]|l?x{0,3})(i[xv]|v?i{0,3})$/', $p)
                ? mb_strtoupper($p) : $m[1];
        }, $valore);

        // Parola preceduta da uno spazio (quindi mai la prima) e seguita da spazio, apostrofo o fine del testo.
        return preg_replace_callback('/(?<=\s)(\p{L}+)(?=[\s\'’]|$)/u',
            fn ($m) => in_array(mb_strtolower($m[1]), self::PARTICELLE, true) ? mb_strtolower($m[1]) : $m[1], $valore);
    }

    /** Dati di una persona (nominativo/referente): ripulisce solo le chiavi presenti, lascia le altre. */
    public static function persona(array $d): array
    {
        return self::applica($d, [
            'nome' => 'titolo', 'cognome' => 'titolo', 'via' => 'luogo', 'comune' => 'luogo',
            'email' => 'minuscolo', 'codice_fiscale' => 'maiuscolo', 'provincia' => 'maiuscolo',
            'telefono' => 'spazi', 'cap' => 'spazi',
            'nascita_comune' => 'luogo', 'nascita_provincia' => 'maiuscolo', 'nascita_stato_altro' => 'luogo',
        ]);
    }

    /** Dati di fatturazione (privato o azienda). */
    public static function fatturazione(array $d): array
    {
        return self::applica($d, [
            'nome' => 'titolo', 'cognome' => 'titolo', 'via' => 'luogo', 'comune' => 'luogo',
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
