<?php

namespace App\Support;

/**
 * Protegge gli indirizzi email scritti nei body di post/pagine da crawler/bot che fanno scraping
 * grezzo dell'HTML: al render pubblico (non nel DB — il testo resta in chiaro per l'editor) ogni
 * indirizzo trovato viene riscritto in entità HTML numeriche carattere per carattere. Il browser lo
 * mostra e lo rende cliccabile (anche dentro un `href="mailto:...">`, le entità sono valide anche lì)
 * normalmente; un bot che legge il markup grezzo non lo riconosce come email.
 *
 * Sostituisce il vecchio shortcode CPWBS `[icoat]` (che rimpiazzava la @ con un'icona).
 */
class EmailObfuscator
{
    private const PATTERN = '~[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}~';

    public static function protect(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return preg_replace_callback(self::PATTERN, fn ($m) => self::toEntities($m[0]), $html);
    }

    private static function toEntities(string $text): string
    {
        $out = '';
        foreach (str_split($text) as $char) {
            $out .= '&#'.ord($char).';';
        }

        return $out;
    }
}
