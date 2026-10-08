<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

/**
 * Pulizia strutturale dell'HTML del corpo, via DOMDocument (ext-dom, nessuna dipendenza nuova).
 *
 * Usata da App\Support\BodyHtml (editor Summernote): stripJunkBlocks() gira come pre-filtro prima di
 * HTMLPurifier, per intercettare markup incollato inconsapevolmente (HTMLPurifier già toglie
 * class/id/data-*, ma non il TESTO dentro quei blocchi).
 *
 * Gli <iframe> non vengono toccati (li governa la whitelist di config/purifier.php).
 */
class HtmlTidy
{
    /** Tag rimossi con tutto il contenuto, ovunque compaiano. */
    private const JUNK_TAGS = ['script', 'style', 'link', 'meta', 'o:p'];

    /** Un nodo è spazzatura se una sua classe inizia con questi prefissi... */
    private const JUNK_CLASS_PREFIXES = ['ssvd'];

    /** ...o se una classe/id è esattamente uno di questi (popup di estensioni "salva pagina"). */
    private const JUNK_TOKENS = ['ssvd', 'popupmenu', 'backmask', 'pop'];

    /** Solo rimozione dei blocchi spazzatura noti. */
    public static function stripJunkBlocks(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        [$doc, $body] = self::load($html);
        self::removeJunk($body);

        return self::save($doc, $body);
    }

    /** @return array{0: DOMDocument, 1: DOMElement} */
    private static function load(string $html): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);

        // Il prologo <?xml encoding> forza il parsing come UTF-8 (altrimenti DOMDocument assume ISO-8859-1).
        $doc->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            // Nessun body (input vuoto/degenere): crea un contenitore vuoto.
            $body = $doc->createElement('body');
            $doc->appendChild($body);
        }

        return [$doc, $body];
    }

    private static function save(DOMDocument $doc, DOMElement $body): string
    {
        $out = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function removeJunk(DOMElement $body): void
    {
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $el) {
            if (! $el instanceof DOMElement || $el->parentNode === null) {
                continue; // già rimosso insieme a un antenato
            }
            if (self::isJunk($el)) {
                $el->parentNode->removeChild($el);
            }
        }
    }

    private static function isJunk(DOMElement $el): bool
    {
        if (in_array(strtolower($el->nodeName), self::JUNK_TAGS, true)) {
            return true;
        }

        $tokens = preg_split(
            '/\s+/',
            strtolower($el->getAttribute('class').' '.$el->getAttribute('id')),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        foreach ($tokens as $token) {
            if (in_array($token, self::JUNK_TOKENS, true)) {
                return true;
            }
            foreach (self::JUNK_CLASS_PREFIXES as $prefix) {
                if (str_starts_with($token, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
