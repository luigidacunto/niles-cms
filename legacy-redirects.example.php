<?php

/**
 * Redirect 301 da URL del vecchio sito verso i nuovi path NILES, per singola installazione.
 *
 * Questo file NON è versionato (vedi .gitignore) — copialo in `legacy-redirects.php` alla radice
 * del progetto e personalizzalo per il comitato/dominio corrente. Se il file non esiste, il
 * meccanismo resta semplicemente disattivato (nessun redirect, nessun overhead).
 *
 * per i dettagli del middleware che lo legge.
 */

return [

    // Redirect puntuali: vecchio path assoluto (senza dominio) => nuovo path assoluto.
    'exact' => [
        // '/storia-e-principi' => '/chi-siamo/statuto',
        // '/' => '/',
    ],

    // Mappa categoria vecchia => categoria nuova, usata dal pattern generico sotto per i post
    // il cui slug di categoria è cambiato (es. rinominata) rispetto al vecchio sito.
    'category_map' => [
        // 'sociale' => 'inclusione-sociale',
    ],

    // Se true, applica il pattern generico /post/{categoria}/{slug} => /{categoria}/{slug}
    // (con la categoria eventualmente rimappata da 'category_map' sopra) — il formato URL usato
    // dal vecchio CMS "CPWBS" per i post. Disattiva se il vecchio sito aveva un altro schema.
    'post_pattern' => true,
];
