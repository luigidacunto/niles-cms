<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Corso;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\PrivacyPolicy;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_only_published_pages_and_posts(): void
    {
        $category = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);

        Page::create(['slug' => 'pagina-pubblicata', 'title' => 'Pubblicata', 'body' => '', 'published' => true, 'order' => 0]);
        Page::create(['slug' => 'pagina-bozza', 'title' => 'Bozza', 'body' => '', 'published' => false, 'order' => 1]);

        Post::create(['category_id' => $category->id, 'slug' => 'post-pubblicato', 'title' => 'Pubblicato', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        Post::create(['category_id' => $category->id, 'slug' => 'post-bozza', 'title' => 'Bozza', 'body' => '<p>x</p>', 'published' => false, 'published_at' => now()]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('/pagina-pubblicata');
        $response->assertDontSee('pagina-bozza');
        $response->assertSee('/news/post-pubblicato');
        $response->assertDontSee('post-bozza');
    }

    public function test_robots_txt_points_to_sitemap_and_blocks_admin_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Disallow: /admin');
        $response->assertSee(route('sitemap'));
    }

    public function test_includes_open_courses_archives_privacy_and_trasparenza_documents(): void
    {
        $category = Category::create(['slug' => 'salute', 'name' => 'Salute', 'order' => 0]);
        Page::create(['slug' => 'salute', 'title' => 'Salute', 'body' => '', 'published' => true, 'order' => 0, 'category_id' => $category->id]);

        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $t = TipologiaCorso::create(['nome' => 'BLSD', 'sigla' => 'BLSD']);
        $nuovo = fn (string $slug, array $extra = []) => Corso::create(array_merge([
            'tipologia_corso_id' => $t->id, 'slug' => $slug, 'protocollo' => strtoupper($slug), 'admin_id' => $admin->id,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4), 'costo' => 0, 'pubblicato' => true,
        ], $extra));
        $nuovo('corso-aperto');
        $nuovo('corso-annullato', ['annullato_at' => now()]);
        $nuovo('corso-bozza', ['pubblicato' => false]);

        PrivacyPolicy::create(['tipo' => 'corsi-popolazione', 'is_default' => true, 'titolo' => 'x', 'testo' => 'x']);

        $doc = fn (string $scope, string $titolo, bool $pub = true) => Document::create([
            'document_category_id' => DocumentCategory::create(['name' => $titolo, 'slug' => $titolo, 'scope' => $scope])->id,
            'title' => $titolo, 'type' => 'altro', 'file_path' => 'x.pdf', 'published' => $pub,
        ]);
        $trasp = $doc('trasparenza', 'trasp');
        $doc('trasparenza', 'bozza', false);
        $lib = $doc('library', 'libreria');
        $soci = $doc('soci', 'riservato');

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['/archivi/archivio-notizie', '/archivi/archivio-comunicati-stampa', '/salute/archivio',
            '/corsi/corso-aperto/iscrizione', '/privacy/corsi-popolazione', '/documenti/'.$trasp->id] as $atteso) {
            $this->assertStringContainsString($atteso, $xml);
        }
        foreach (['corso-annullato', 'corso-bozza', '/privacy/soci', '/documenti/'.$lib->id.'<', '/documenti/'.$soci->id.'<'] as $escluso) {
            $this->assertStringNotContainsString($escluso, $xml);
        }
        $this->assertSame(1, substr_count($xml, '/documenti/'), 'solo il documento di Trasparenza pubblicato (la libreria non usata resta fuori)');
    }

    public function test_robots_in_produzione_blocca_aree_non_pubbliche(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin/')->assertSee('Disallow: /soci/')->assertSee('Disallow: /news/altre')
            ->assertSee('Disallow: /corsi/*/iscrizione/confermata');
    }

    public function test_documenti_libreria_solo_se_usati_da_contenuti_pubblici(): void
    {
        $cat = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);
        $post = fn (bool $pub) => Post::create(['category_id' => $cat->id, 'slug' => 'p'.random_int(1, 99999), 'title' => 't', 'body' => '<p>x</p>', 'published' => $pub, 'published_at' => now()]);
        $doc = fn (string $scope, string $nome) => Document::create([
            'document_category_id' => DocumentCategory::create(['name' => $nome, 'slug' => $nome, 'scope' => $scope])->id,
            'title' => $nome, 'type' => 'altro', 'file_path' => 'x.pdf', 'published' => true,
        ]);

        $allegato = $doc('library', 'allegato');
        $inline = $doc('library', 'inline');
        $inBozza = $doc('library', 'inbozza');
        $orfano = $doc('library', 'orfano');
        $riservato = $doc('soci', 'riservato');

        $post(true)->attachments()->attach($allegato->id);
        $post(false)->attachments()->attach($inBozza->id);
        $post(true)->attachments()->attach($riservato->id); // anche se allegato: mai i riservati ai soci
        Page::create(['slug' => 'pg', 'title' => 'Pg', 'published' => true, 'order' => 0,
            'body' => '<p><a href="/documenti/'.$inline->id.'">file</a></p>']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('/documenti/'.$allegato->id.'<', $xml);
        $this->assertStringContainsString('/documenti/'.$inline->id.'<', $xml);
        foreach ([$inBozza, $orfano, $riservato] as $escluso) {
            $this->assertStringNotContainsString('/documenti/'.$escluso->id.'<', $xml);
        }
    }
}
