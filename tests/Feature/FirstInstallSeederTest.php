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
        $this->assertFalse($principi->published); // le sezioni nascono in bozza: le attiva il comitato
        $this->assertSame(Category::where('slug', 'principi-e-valori')->value('id'), $principi->category_id);
        $this->assertNotEmpty($principi->body);
        $this->assertNotEmpty($principi->excerpt);

        // Sezione prevista ma non usata da tutti: in bozza e fuori dal menu.
        $innovazione = Page::where('slug', 'innovazione')->first();
        $this->assertFalse($innovazione->published);
        $this->assertFalse($innovazione->in_menu);
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
