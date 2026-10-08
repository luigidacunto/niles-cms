<?php

namespace App\Support;

use App\Models\IscrizioneCorso;

/**
 * Promemoria per il calendario (.ics, RFC 5545) di un'iscrizione a un corso: un evento con data, ora, luogo
 * e un avviso il giorno prima. Orari in UTC (`Z`), così funzionano ovunque senza VTIMEZONE: gli orari salvati
 * sono ora locale del comitato e vengono convertiti da Corso::inizioLocale()/fineLocale().
 */
class IcsEvento
{
    public static function da(IscrizioneCorso $iscrizione): string
    {
        $corso = $iscrizione->corso;
        $descrizione = "Protocollo {$corso->protocollo}. ".route('corsi.iscrizione.show', $corso);

        $righe = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//NILES//Corsi//IT',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:iscrizione-'.$iscrizione->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$corso->inizioLocale()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$corso->fineLocale()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::testo($corso->tipologia->nome),
            'DESCRIPTION:'.self::testo($descrizione),
        ];
        if ($corso->sedeLabel()) {
            $righe[] = 'LOCATION:'.self::testo($corso->sedeLabel());
        }
        array_push($righe,
            'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:'.self::testo($corso->tipologia->nome), 'TRIGGER:-P1D', 'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR'
        );

        return implode("\r\n", array_map([self::class, 'piega'], $righe))."\r\n";
    }

    /** Escape dei valori di testo (RFC 5545 §3.3.11). */
    private static function testo(string $valore): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], $valore);
    }

    /** Righe lunghe max 75 byte: il resto va a capo con uno spazio iniziale, senza spezzare caratteri multibyte. */
    private static function piega(string $riga): string
    {
        if (strlen($riga) <= 75) {
            return $riga;
        }

        $out = '';
        $corrente = '';
        $limite = 75;
        foreach (mb_str_split($riga) as $c) {
            if (strlen($corrente) + strlen($c) > $limite) {
                $out .= $corrente."\r\n ";
                $corrente = '';
                $limite = 74; // lo spazio di continuazione conta come 1 byte
            }
            $corrente .= $c;
        }

        return $out.$corrente;
    }
}
