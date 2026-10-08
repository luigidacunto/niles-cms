<?php

/*
 * Sanitizzazione dell'HTML del corpo (`body`) di post e pagine, che viene scritto anche dagli editor
 * tramite Summernote / codeview. Applicata da App\Support\BodyHtml (trait App\Models\Concerns\PurifiesBody).
 *
 * NON è un motore di widget: niente <script>, niente <form>, niente attributi on*=, niente javascript:.
 * Gli <iframe> passano SOLO dai domini nella whitelist qui sotto (video, mappe, moduli Google).
 *
 * Chiavi = opzioni HTMLPurifier (http://htmlpurifier.org/live/configdoc/plain.html), passate 1:1.
 */

return [

    // Tag ammessi nel body: testo, titoli, liste, tabelle, link, immagini + <iframe> (regolato sotto).
    'HTML.Allowed' => implode(',', [
        'p', 'br', 'span', 'div', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'small',
        'blockquote', 'pre', 'code',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li',
        'a[href|title|target|rel]',
        'img[src|alt|title]',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'hr',
        'iframe[src|width|height|frameborder|allow|allowfullscreen|title|loading|style]',
    ]),

    // style="" consentito ma ristretto a proprietà innocue (iframe responsive, allineamenti tabella).
    'CSS.AllowedProperties' => 'text-align,width,height,max-width,border,aspect-ratio',

    // <iframe> abilitati ma SOLO verso questi host. Aggiungere un servizio = aggiungere un ramo alla regex.
    'HTML.SafeIframe' => true,
    'URI.SafeIframeRegexp' =>
        '%^https://('
        .'www\.youtube(?:-nocookie)?\.com/embed/'
        .'|player\.vimeo\.com/video/'
        .'|www\.google\.com/maps/embed'
        .'|docs\.google\.com/forms/'
        .')%',

    // target="_blank" senza rel="noopener": lo aggiunge HTMLPurifier.
    'HTML.TargetBlank' => true,

    // Toglie i tag vuoti lasciati dall'editor (<p></p>, <span></span>...), ma non gli elementi che
    // sono "vuoti" per natura (iframe con src, celle di tabella).
    'AutoFormat.RemoveEmpty' => true,
    'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
    'AutoFormat.RemoveEmpty.Predicate' => [
        'iframe' => ['src'],
        'td' => [], 'th' => [], 'colgroup' => [],
    ],
];
