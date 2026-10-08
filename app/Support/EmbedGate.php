<?php

namespace App\Support;

/**
 * Sostituisce gli <iframe> di terze parti (Google Maps, YouTube, Google Forms, Vimeo, ...) trovati nei
 * body di post/pagine con un placeholder "clicca per caricare": l'iframe reale non viene mai inserito
 * nel DOM finché il visitatore non clicca, quindi nessun cookie/richiesta di terze parti parte prima di
 * un'azione esplicita (GDPR — niente da aggiungere al banner cookie esistente, è un consenso puntuale
 * per-embed, non una categoria).
 *
 * Non tocca `pages.embed_html` (widget donazioni admin-only, spesso <script> non <iframe> — fuori
 * ambito, volume basso).
 */
class EmbedGate
{
    private const PATTERN = '~<iframe\b[^>]*\bsrc="(https?://[^"]+)"[^>]*\bwidth="(\d+)"[^>]*\bheight="(\d+)"[^>]*>.*?</iframe>~is';

    private const LABELS = [
        'google.com/maps' => 'Google Maps',
        'youtube.com' => 'YouTube',
        'youtube-nocookie.com' => 'YouTube',
        'docs.google.com/forms' => 'Google Forms',
        'vimeo.com' => 'Vimeo',
    ];

    public static function protect(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        return preg_replace_callback(self::PATTERN, fn ($m) => self::placeholder($m[1], (int) $m[2], (int) $m[3]), $html);
    }

    private static function placeholder(string $src, int $width, int $height): string
    {
        $label = self::label($src);
        $ratio = $width.'/'.$height;

        return '<div class="embed-gate not-prose my-6" x-data="{ loaded: false }" style="max-width:'.$width.'px">'
            .'<button type="button" x-show="!loaded" @click="loaded = true" style="aspect-ratio:'.$ratio.'"'
            .' class="w-full flex flex-col items-center justify-center gap-2 rounded border border-gray-200 bg-gray-50 text-sm text-gray-600 hover:bg-gray-100">'
            .'<i class="fas fa-external-link-alt fa-lg" aria-hidden="true"></i>'
            .'<span>Carica contenuto di '.$label.'</span>'
            .'</button>'
            .'<template x-if="loaded">'
            .'<iframe src="'.$src.'" width="'.$width.'" height="'.$height.'" style="max-width:100%" frameborder="0" allowfullscreen></iframe>'
            .'</template>'
            .'</div>';
    }

    private static function label(string $src): string
    {
        foreach (self::LABELS as $needle => $label) {
            if (str_contains($src, $needle)) {
                return $label;
            }
        }

        return 'terze parti';
    }
}
