<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Str;

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

    /** Valori dei caratteri in posizione dispari (1ª, 3ª…) per il carattere di controllo, indicizzati dal valore del carattere (0-9 / A=0…Z=25). */
    private const DISPARI = [1, 0, 5, 7, 9, 13, 15, 17, 19, 21, 2, 4, 18, 20, 11, 3, 6, 8, 12, 14, 16, 10, 22, 25, 24, 23];

    /** Carattere di controllo (16ª posizione) calcolato sui primi 15 caratteri: somma dei valori (tabella diversa per posizione dispari/pari) modulo 26, come lettera. */
    public static function carattereControllo(string $cf15): string
    {
        $somma = 0;
        foreach (str_split(strtoupper($cf15)) as $i => $c) {
            $v = ctype_digit($c) ? (int) $c : ord($c) - 65;
            $somma += $i % 2 === 0 ? self::DISPARI[$v] : $v;
        }

        return chr(65 + $somma % 26);
    }

    /**
     * Controlli aggiuntivi per le tipologie con anagrafica completa: carattere di controllo corretto, data di nascita indicata
     * uguale a quella del codice, Italia/estero coerente con la lettera «Z» del codice catastale (comune di nascita, 12ª posizione;
     * ⚠️ il comune in sé non si verifica: servirebbe la tabella catastale). Lista vuota = tutto torna. $stato = nome dello Stato
     * estero scelto, solo per il testo del messaggio.
     *
     * @return list<string>
     */
    public static function incoerenzeAnagrafica(string $cf, ?Carbon $dataNascita, ?bool $estero, ?string $stato = null): array
    {
        $cf = strtoupper(trim($cf));
        if (! preg_match('/^[A-Z0-9]{16}$/', $cf)) {
            return [];   // il formato lo segnala già la regola CodiceFiscale
        }

        $problemi = [];

        if (self::carattereControllo(substr($cf, 0, 15)) !== $cf[15]) {
            $problemi[] = "L'ultima lettera del codice fiscale (carattere di controllo) non è corretta: controlla di averlo scritto bene.";
        }

        $dalCodice = self::dataNascita($cf);
        if ($dataNascita !== null && $dalCodice !== null
            && ($dataNascita->day !== $dalCodice->day || $dataNascita->month !== $dalCodice->month || $dataNascita->year % 100 !== $dalCodice->year % 100)) {
            $problemi[] = 'La data di nascita indicata non corrisponde a quella del codice fiscale.';
        }

        if ($estero !== null && $estero !== ($cf[11] === 'Z')) {
            $problemi[] = $estero
                ? 'Il codice fiscale è di una persona nata in Italia, ma come luogo di nascita è indicato '
                    .($stato ? "«{$stato}»" : 'uno Stato estero').': correggi il luogo di nascita o il codice fiscale.'
                : "Il codice fiscale è di una persona nata all'estero, ma come luogo di nascita è indicata l'Italia: correggi il luogo di nascita o il codice fiscale.";
        }

        return $problemi;
    }

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

    /**
     * Incoerenze del codice fiscale, in italiano e per parte (lista vuota = tutto torna). Si controllano le prime 11 posizioni:
     * lettere di cognome/nome (3+3, confrontate con quelli indicati se presenti) e data di nascita (anno, mese, giorno).
     * ⚠️ Le ultime 5 (codice del comune + carattere di controllo) NON si verificano: il comune richiederebbe la tabella
     * catastale; chi parte da qui può estendere (il carattere di controllo è calcolabile, ma si è scelto di non usarlo).
     * Omocodia gestita (cifre sostituite da LMNPQRSTUV).
     *
     * @return list<string>
     */
    public static function incoerenze(string $cf, ?string $nome = null, ?string $cognome = null): array
    {
        $cf = strtoupper(trim($cf));
        if (! preg_match('/^[A-Z0-9]{16}$/', $cf)) {
            return ['Il codice fiscale deve essere di 16 caratteri alfanumerici.'];
        }

        $problemi = [];

        if (! preg_match('/^[A-Z]{6}$/', substr($cf, 0, 6))) {
            $problemi[] = 'Le prime 6 posizioni del codice fiscale (da cognome e nome) devono essere lettere.';
        } else {
            $problemi = [...$problemi, ...self::incoerenzeNomi($cf, $nome, $cognome)];
        }

        if (! preg_match('/^[0-9LMNPQRSTUV]{2}$/', substr($cf, 6, 2))) {
            $problemi[] = 'L\'anno di nascita nel codice fiscale (7ª e 8ª posizione) non è valido.';
        }

        $mese = self::MESI[$cf[8]] ?? null;
        if ($mese === null) {
            $problemi[] = 'Il mese di nascita nel codice fiscale (9ª posizione) non è valido.';
        }

        $giorno = preg_match('/^[0-9LMNPQRSTUV]{2}$/', substr($cf, 9, 2)) ? (int) self::cifre(substr($cf, 9, 2)) : 0;
        $giorno = $giorno > 40 ? $giorno - 40 : $giorno;                      // le donne hanno +40
        // Anno bisestile come riferimento: il 29/02 è ammesso (l'anno è a 2 cifre e il secolo non è noto).
        if ($giorno < 1 || ($mese !== null && ! checkdate($mese, $giorno, 2000))) {
            $problemi[] = 'Il giorno di nascita nel codice fiscale (10ª e 11ª posizione) non è valido per il mese indicato.';
        }

        return $problemi;
    }

    /** @return list<string> */
    private static function incoerenzeNomi(string $cf, ?string $nome, ?string $cognome): array
    {
        $codiceCognome = $cognome !== null ? self::codiceCognome($cognome) : null;
        $codiceNome = $nome !== null ? self::codiceNome($nome) : null;
        $cfCognome = substr($cf, 0, 3);
        $cfNome = substr($cf, 3, 3);

        $cognomeOk = $codiceCognome === null || $codiceCognome === $cfCognome;
        $nomeOk = $codiceNome === null || $codiceNome === $cfNome;

        if (! $cognomeOk && ! $nomeOk && $codiceCognome === $cfNome && $codiceNome === $cfCognome) {
            return ['Nome e cognome sembrano invertiti rispetto al codice fiscale.'];
        }

        $problemi = [];
        if (! $cognomeOk) {
            $problemi[] = "Il codice fiscale non corrisponde al cognome «{$cognome}» (le prime 3 lettere derivano dal cognome).";
        }
        if (! $nomeOk) {
            $problemi[] = "Il codice fiscale non corrisponde al nome «{$nome}» (la 4ª, 5ª e 6ª lettera derivano dal nome).";
        }

        return $problemi;
    }

    /** Lettere (A-Z, senza accenti) del nome, divise in consonanti e vocali; null se non ce ne sono. */
    private static function lettere(string $s): ?array
    {
        $l = preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($s)));

        return $l === '' ? null : [preg_replace('/[AEIOU]/', '', $l), preg_replace('/[^AEIOU]/', '', $l)];
    }

    private static function codiceCognome(string $cognome): ?string
    {
        $l = self::lettere($cognome);

        return $l === null ? null : substr($l[0].$l[1].'XXX', 0, 3);
    }

    private static function codiceNome(string $nome): ?string
    {
        $l = self::lettere($nome);
        if ($l === null) {
            return null;
        }

        // Con 4 o più consonanti si usano la 1ª, la 3ª e la 4ª.
        return strlen($l[0]) >= 4 ? $l[0][0].$l[0][2].$l[0][3] : substr($l[0].$l[1].'XXX', 0, 3);
    }

    private static function cifre(string $s): string
    {
        return implode('', array_map(fn ($c) => ctype_digit($c) ? $c : self::OMOCODIA[$c], str_split($s)));
    }
}
