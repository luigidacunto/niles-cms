<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\DocumentCategory;
use App\Models\EmailTemplate;
use App\Models\Page;
use App\Models\PrivacyPolicy;
use App\Models\TipologiaCorso;
use Database\Seeders\FirstInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FirstInstallSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_il_primo_admin_con_le_credenziali_da_configurazione(): void
    {
        config(['app.initial_admin.email' => 'nome@dominio.it', 'app.initial_admin.password' => 'segreta-123']);

        (new FirstInstallSeeder)->run();

        $admin = Admin::firstOrFail();
        $this->assertSame('nome@dominio.it', $admin->email);
        $this->assertTrue(Hash::check('segreta-123', $admin->password));
        $this->assertTrue((bool) $admin->protected);
    }

    public function test_senza_password_configurata_ne_genera_una_casuale(): void
    {
        config(['app.initial_admin.password' => null]);

        (new FirstInstallSeeder)->run();

        $admin = Admin::firstOrFail();
        $this->assertSame('administrator@example.it', $admin->email);
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_non_crea_un_secondo_admin_se_ne_esiste_gia_uno(): void
    {
        (new FirstInstallSeeder)->run();
        (new FirstInstallSeeder)->run();

        $this->assertSame(1, Admin::count());
    }

    public function test_crea_il_contenuto_di_base_del_sito(): void
    {
        (new FirstInstallSeeder)->run();

        $this->assertSame(9, Category::count());
        $this->assertSame(5, TipologiaCorso::count());
        $this->assertTrue(DocumentCategory::where('slug', 'organigramma')->where('selectable', false)->exists());
        $this->assertDatabaseHas('document_categories', ['slug' => 'riservati-soci', 'scope' => 'soci']);
        $this->assertDatabaseHas('comunicazioni_soci', ['slug' => 'benvenuto-nell-area-soci']);
        $this->assertSame(['corsi-popolazione', 'soci'], PrivacyPolicy::where('is_default', true)->orderBy('tipo')->pluck('tipo')->all());
        $this->assertSame(
            array_keys(config('email_templates')),
            EmailTemplate::where('is_default', true)->orderBy('id')->pluck('tipo')->all()
        );
    }

    public function test_pagine_di_sistema_e_struttura_del_menu(): void
    {
        (new FirstInstallSeeder)->run();

        foreach (['trasparenza', 'struttura-organizzativa', 'donazioni', 'corsi-di-formazione', 'privacy', 'cookie-policy', 'salute', 'innovazione'] as $slug) {
            $this->assertTrue((bool) Page::where('slug', $slug)->value('system'), $slug);
        }

        $this->assertSame('corsi', Page::where('slug', 'corsi-di-formazione')->value('template'));
        $this->assertSame(Page::where('slug', 'servizi')->value('id'), Page::where('slug', 'corsi-di-formazione')->value('parent_id'));

        $principi = Page::where('slug', 'principi-e-valori-umanitari')->first();
        $this->assertTrue($principi->published); // il menu nasce visibile, con il testo introduttivo standard
        $this->assertSame(Category::where('slug', 'principi-e-valori')->value('id'), $principi->category_id);
        $this->assertNotEmpty($principi->body);
        $this->assertNotEmpty($principi->excerpt);

        // Sezione prevista ma non usata da tutti: in bozza e fuori dal menu.
        $innovazione = Page::where('slug', 'innovazione')->first();
        $this->assertFalse($innovazione->published);
        $this->assertFalse($innovazione->in_menu);
    }

    public function test_il_menu_nasce_visibile_con_le_voci_standard(): void
    {
        (new FirstInstallSeeder)->run();

        $albero = Page::menuTree();
        $this->assertSame(
            ['Chi Siamo', 'Cosa Facciamo', 'Volontariato', 'Sostienici', 'Servizi', 'Contatti'],
            $albero->pluck('title')->all()
        );

        $figli = fn (string $titolo) => $albero->firstWhere('title', $titolo)->children->pluck('title')->all();
        $this->assertSame(['Storia e Principi', 'Statuto', 'Struttura Organizzativa'], $figli('Chi Siamo'));
        $this->assertSame(['Diventa Volontario'], $figli('Volontariato'));
        $this->assertSame(['Dona il 5x1000'], $figli('Sostienici'));
        $this->assertSame(['Corsi di Formazione'], $figli('Servizi'));
        $this->assertSame(['Contattaci', 'Dove Trovarci'], $figli('Contatti'));
        $this->assertCount(6, $figli('Cosa Facciamo'));

        // Pagine che non tutti i comitati hanno: presenti ma in bozza.
        foreach (['corpo-infermiere-volontarie', 'diventa-infermiera-volontaria', 'donazioni'] as $slug) {
            $this->assertFalse((bool) Page::where('slug', $slug)->value('published'), $slug);
        }

        // Testi standard: niente riferimenti locali; i dati del comitato sono segnaposto da completare.
        foreach (['storia-e-principi', 'statuto', 'corpo-infermiere-volontarie', 'diventa-volontario', 'diventa-infermiera-volontaria'] as $slug) {
            $body = Page::where('slug', $slug)->value('body');
            $this->assertNotEmpty($body, $slug);
            $this->assertDoesNotMatchRegularExpression('/arezzo|aretin|criarezzo|\/storage\//i', $body, $slug);
        }
        $this->assertStringContainsString('Da completare', Page::where('slug', 'contattaci')->value('body'));
        $this->assertStringContainsString('Da completare', Page::where('slug', 'dona-il-5x1000')->value('body'));

        // Ordine coerente: nessun duplicato tra fratelli.
        foreach (Page::all()->groupBy('parent_id') as $gruppo) {
            $this->assertSame(
                $gruppo->count(),
                $gruppo->pluck('order')->unique()->count(),
                $gruppo->map(fn ($p) => $p->slug.':'.$p->order)->implode(', ')
            );
        }
    }

    public function test_un_secondo_lancio_non_tocca_le_personalizzazioni(): void
    {
        (new FirstInstallSeeder)->run();
        Page::where('slug', 'salute')->update(['body' => '<p>Testo del comitato.</p>']);

        (new FirstInstallSeeder)->run();

        $this->assertSame('<p>Testo del comitato.</p>', Page::where('slug', 'salute')->value('body'));
        $this->assertSame(1, Page::where('slug', 'salute')->count());
    }
}
