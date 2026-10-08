<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\Page;
use App\Models\Post;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_valid_organization_jsonld(): void
    {
        CommitteeInfo::current()->update(['denominazione' => 'Comitato Test', 'telefono' => '000']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('"@context":"https://schema.org"', false);
        $response->assertSee('"@type":"NGO"', false);
        $response->assertDontSee('$__contextArgs', false);
    }

    public function test_post_page_has_og_tags_and_newsarticle_jsonld(): void
    {
        $category = Category::create(['slug' => 'news', 'name' => 'News', 'order' => 0]);
        $post = Post::create([
            'category_id' => $category->id, 'slug' => 'test-post', 'title' => 'Titolo di prova',
            'excerpt' => 'Un estratto di prova', 'body' => '<p>corpo</p>',
            'published' => true, 'published_at' => now(),
        ]);

        $response = $this->get("/news/{$post->slug}");

        $response->assertOk();
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertSee('Titolo di prova', false);
        $response->assertSee('"@type":"NewsArticle"', false);
        $response->assertDontSee('$__contextArgs', false);
    }

    public function test_page_meta_description_uses_excerpt(): void
    {
        $page = Page::create([
            'slug' => 'test-page', 'title' => 'Pagina di prova', 'excerpt' => 'Descrizione della pagina',
            'body' => '<p>corpo</p>', 'published' => true, 'order' => 0,
        ]);

        $this->get("/{$page->slug}")->assertSee('name="description" content="Descrizione della pagina"', false);
    }

    /** Estrae e decodifica tutti i blocchi JSON-LD di una risposta. */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(fn ($j) => json_decode($j, true, 512, JSON_THROW_ON_ERROR), $m[1]);
    }

    private function tipo(array $blocchi, string $tipo): ?array
    {
        return collect($blocchi)->firstWhere('@type', $tipo);
    }

    public function test_organization_jsonld_ha_sameas_solo_con_social_compilati(): void
    {
        $this->assertArrayNotHasKey('sameAs', $this->tipo($this->jsonLd($this->get('/')->getContent()), 'NGO'));

        CommitteeInfo::current()->update(['facebook_url' => 'https://facebook.com/x', 'x_url' => 'https://x.com/y']);

        $org = $this->tipo($this->jsonLd($this->get('/')->getContent()), 'NGO');
        $this->assertSame(['https://facebook.com/x', 'https://x.com/y'], $org['sameAs']);
    }

    public function test_breadcrumb_jsonld_su_pagine_e_notizie(): void
    {
        $padre = Page::create(['slug' => 'padre', 'title' => 'Padre', 'body' => '', 'published' => true, 'order' => 0]);
        Page::create(['slug' => 'figlia', 'title' => 'Figlia', 'body' => '', 'published' => true, 'order' => 0, 'parent_id' => $padre->id]);

        $bc = $this->tipo($this->jsonLd($this->get('/figlia')->getContent()), 'BreadcrumbList');
        $this->assertSame(['Home', 'Padre', 'Figlia'], array_column($bc['itemListElement'], 'name'));
        $this->assertSame([1, 2, 3], array_column($bc['itemListElement'], 'position'));
        $this->assertStringEndsWith('/figlia', end($bc['itemListElement'])['item']);

        $cat = Category::create(['slug' => 'news', 'name' => 'News', 'order' => 0]);
        Post::create(['category_id' => $cat->id, 'slug' => 'nota', 'title' => 'Nota', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        $bc = $this->tipo($this->jsonLd($this->get('/news/nota')->getContent()), 'BreadcrumbList');
        $this->assertSame(['Home', 'Nota'], array_column($bc['itemListElement'], 'name'));
    }

    public function test_corso_aperto_ha_event_jsonld(): void
    {
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $t = TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD', 'immagine' => 'corsi/tipologie/blsd.jpg']);
        Corso::create(['tipologia_corso_id' => $t->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1', 'admin_id' => $admin->id,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4), 'costo' => 35, 'pubblicato' => true,
            'usa_indirizzo_comitato' => true]);
        CommitteeInfo::current()->update(['indirizzo' => 'Via Roma 1, Bologna']);

        $ev = $this->tipo($this->jsonLd($this->get('/corsi/blsd-1/iscrizione')->getContent()), 'Event');

        $this->assertSame('Corso BLSD', $ev['name']);
        $this->assertSame('Via Roma 1, Bologna', $ev['location']['name']);
        $this->assertSame('35.00', $ev['offers']['price']);
        $this->assertSame('EUR', $ev['offers']['priceCurrency']);
        $this->assertStringEndsWith('/storage/corsi/tipologie/blsd.jpg', $ev['image']);
        // Ora locale italiana con il suo offset (+01:00/+02:00), mai l'UTC dell'app.
        $this->assertMatchesRegularExpression('/T\\d\\d:\\d\\d:\\d\\d\\+0[12]:00$/', $ev['startDate']);
    }
}
