<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Sanitizza l'HTML del corpo di post e pagine secondo la whitelist di config/purifier.php.
 * Unico punto in cui gira HTMLPurifier per il contenuto redazionale; usato dal mutator del trait
 * App\Models\Concerns\PurifiesBody (Post, Page).
 */
class BodyHtml
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        // Pre-filtro: toglie i blocchi spazzatura noti (popup dell'estensione "ssvd_", <script>/<style>...)
        // col loro TESTO — HTMLPurifier scarterebbe gli attributi ma lascerebbe il contenuto dentro.
        $html = HtmlTidy::stripJunkBlocks($html);

        $clean = self::purifier()->purify($html);

        return trim($clean) === '' ? null : $clean;
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();

            foreach ((array) config('purifier', []) as $key => $value) {
                $config->set($key, $value);
            }

            // HTMLPurifier serializza su disco la HTMLDefinition: serve una cartella scrivibile,
            // altrimenti tenta di scrivere dentro vendor/. Con DefinitionID/Rev la ricostruisce
            // solo quando cambia la rev (da alzare a mano se si toccano gli addAttribute qui sotto).
            $cache = storage_path('framework/cache/htmlpurifier');
            if (! is_dir($cache)) {
                mkdir($cache, 0775, true);
            }
            $config->set('Cache.SerializerPath', $cache);
            $config->set('HTML.DefinitionID', 'niles-body');
            $config->set('HTML.DefinitionRev', 1);

            // Attributi <iframe> che HTMLPurifier non conosce di suo (allowfullscreen/frameborder sì).
            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addAttribute('iframe', 'allow', 'Text');
                $def->addAttribute('iframe', 'allowfullscreen', 'Bool');
                $def->addAttribute('iframe', 'loading', 'Enum#lazy,eager,auto');
            }

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}
