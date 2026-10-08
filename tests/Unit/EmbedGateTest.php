<?php

namespace Tests\Unit;

use App\Support\EmbedGate;
use PHPUnit\Framework\TestCase;

class EmbedGateTest extends TestCase
{
    public function test_replaces_iframe_with_click_to_load_placeholder(): void
    {
        $body = '<p><iframe src="https://www.youtube.com/embed/abc123" width="560" height="315" frameborder="0" allowfullscreen=""></iframe></p>';

        $out = EmbedGate::protect($body);

        // L'iframe reale esiste solo dentro <template x-if="loaded">: non viene mai renderizzato nel
        // DOM (nessuna richiesta di terze parti) finché il visitatore non clicca.
        $this->assertStringContainsString('<template x-if="loaded"><iframe src="https://www.youtube.com/embed/abc123"', $out);
        $this->assertStringContainsString('x-data="{ loaded: false }"', $out);
        $this->assertStringContainsString('Carica contenuto di YouTube', $out);
    }

    public function test_labels_google_maps_and_unknown_embeds(): void
    {
        $maps = '<iframe src="https://www.google.com/maps/embed?pb=xyz" width="600" height="450"></iframe>';
        $this->assertStringContainsString('Google Maps', EmbedGate::protect($maps));

        $unknown = '<iframe src="https://example.org/widget" width="300" height="200"></iframe>';
        $this->assertStringContainsString('terze parti', EmbedGate::protect($unknown));
    }

    public function test_leaves_body_without_iframes_untouched(): void
    {
        $body = '<p>Testo normale, nessun embed.</p>';

        $this->assertSame($body, EmbedGate::protect($body));
    }

    public function test_null_and_empty_pass_through(): void
    {
        $this->assertNull(EmbedGate::protect(null));
        $this->assertSame('', EmbedGate::protect(''));
    }
}
