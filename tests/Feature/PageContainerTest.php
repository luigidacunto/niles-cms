<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageContainerTest extends TestCase
{
    use RefreshDatabase;

    private function pagina(string $slug, ?Page $padre = null, string $body = '', array $extra = []): Page
    {
        return Page::unguarded(fn () => Page::create($extra + [
            'slug' => $slug, 'title' => ucfirst($slug), 'body' => $body, 'published' => true,
            'in_menu' => true, 'parent_id' => $padre?->id, 'order' => 0,
        ]));
    }

    public function test_pagina_padre_elenca_le_figlie_visibili_nel_menu(): void
    {
        $contatti = $this->pagina('contatti');
        $this->pagina('contattaci', $contatti, '<p>Recapiti</p>', ['excerpt' => 'Come raggiungerci', 'order' => 0]);
        $this->pagina('dove-trovarci', $contatti, '<p>Sede</p>', ['order' => 1]);
        $this->pagina('bozza', $contatti, '', ['published' => false]);
        $this->pagina('fuori-menu', $contatti, '', ['in_menu' => false]);

        $html = $this->get('/contatti')->assertOk()->assertSee('Approfondisci')->getContent();

        $this->assertStringContainsString('href="'.url('/contattaci').'"', $html);
        $this->assertStringContainsString('Come raggiungerci', $html);
        $this->assertStringContainsString('href="'.url('/dove-trovarci').'"', $html);
        $this->assertStringNotContainsString('href="'.url('/bozza').'"', $html);
        $this->assertStringNotContainsString('href="'.url('/fuori-menu').'"', $html);
        // Ordine del menu.
        $this->assertLessThan(strpos($html, url('/dove-trovarci')), strpos($html, url('/contattaci')));
    }

    public function test_la_padre_con_un_testo_lo_mostra_prima_dell_elenco(): void
    {
        $chi = $this->pagina('chi-siamo', null, '<p>Introduzione al comitato</p>');
        $this->pagina('statuto', $chi, '<p>Statuto</p>');

        $html = $this->get('/chi-siamo')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Approfondisci'), strpos($html, 'Introduzione al comitato'));
        $this->assertStringContainsString('href="'.url('/statuto').'"', $html);
    }

    public function test_pagina_senza_figlie_non_mostra_l_elenco(): void
    {
        $this->pagina('vuota');
        $this->pagina('padre-senza-figlie-visibili', null, '<p>x</p>');
        $this->pagina('nascosta', Page::where('slug', 'padre-senza-figlie-visibili')->first(), '', ['published' => false]);

        $this->get('/vuota')->assertOk()->assertDontSee('Approfondisci');
        $this->get('/padre-senza-figlie-visibili')->assertOk()->assertDontSee('Approfondisci');
    }

    public function test_il_breadcrumb_linka_la_voce_padre(): void
    {
        $contatti = $this->pagina('contatti');
        $this->pagina('contattaci', $contatti, '<p>Recapiti</p>');

        $this->get('/contattaci')->assertOk()->assertSee('href="'.url('/contatti').'"', false);
    }
}
