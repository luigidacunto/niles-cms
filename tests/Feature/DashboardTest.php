<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;
use App\Models\Member;
use App\Models\Persona;
use App\Models\Post;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', array $perms = [], bool $allCats = true): Admin
    {
        return Admin::create([
            'name' => 'Mario Rossi', 'email' => $role.'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => $perms, 'all_categories' => $allCats,
        ]);
    }

    public function test_dashboard_renders_with_content_stats(): void
    {
        $cat = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);
        Post::create(['category_id' => $cat->id, 'slug' => 'p1', 'title' => 'Primo post', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        Post::create(['category_id' => $cat->id, 'slug' => 'p2', 'title' => 'Bozza post', 'body' => '<p>x</p>', 'published' => false, 'published_at' => now()]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Ciao, Mario')
            ->assertSee('Primo post')
            ->assertSee('Bozza post')
            ->assertSee('Post per categoria');
    }

    public function test_editor_without_pages_permission_sees_no_pages_card(): void
    {
        $editor = $this->admin('editor', ['posts' => ['read']], false);

        $this->actingAs($editor, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(route('admin.pages.index'));
    }

    private function corsoConBlocco(): Corso
    {
        $admin = Admin::first() ?? $this->admin();
        $corso = Corso::create([
            'tipologia_corso_id' => TipologiaCorso::firstOrCreate(['sigla' => 'BLSD'], ['nome' => 'Corso BLSD'])->id, 'slug' => 'blsd-1',
            'protocollo' => 'BLSD-2026-001', 'admin_id' => $admin->id, 'costo' => 35, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
        $blocco = DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => 'Anna', 'cognome' => 'Bianchi', 'metodo_pagamento' => 'Bonifico']);
        $persona = Persona::create(['nome' => 'Anna', 'cognome' => 'Bianchi', 'codice_fiscale' => 'BNCNNA80A41D612X', 'email' => 'anna@example.test']);
        IscrizioneCorso::create([
            'corso_id' => $corso->id, 'dati_fatturazione_corso_id' => $blocco->id, 'persona_id' => $persona->id, 'nome' => 'Anna',
            'cognome' => 'Bianchi', 'email' => 'anna@example.test', 'codice_fiscale' => $persona->codice_fiscale, 'privacy_accettata_at' => now(),
        ]);

        return $corso;
    }

    public function test_admin_vede_formazione_ma_non_pagamenti_e_area_soci_se_spenti(): void
    {
        $admin = $this->admin();
        $this->corsoConBlocco();

        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()
            ->assertSee('Corsi con iscrizioni aperte')->assertSee('Prossimi corsi')->assertSee('BLSD-2026-001')
            ->assertDontSee('Pagamenti da fatturare')->assertDontSee('Membri attivi');
    }

    public function test_pagamenti_attivi_mostrano_i_totali_anche_a_chi_ha_solo_quel_permesso(): void
    {
        CommitteeInfo::current()->update(['pagamenti_corsi_attivi' => true]);
        $this->corsoConBlocco();
        $editor = $this->admin('editor', ['pagamenti_corsi' => ['read', 'write']]);

        $this->actingAs($editor, 'admin')->get('/admin')->assertOk()
            ->assertSee('Pagamenti da fatturare')->assertSee('Pagamenti da incassare')
            ->assertDontSee('Corsi con iscrizioni aperte');
    }

    public function test_area_soci_rispetta_permessi_e_registri_solo_agli_admin(): void
    {
        CommitteeInfo::current()->update(['area_soci_attiva' => true]);
        Member::create(['cognome' => 'Rossi', 'nome' => 'Mario', 'codice_fiscale' => 'RSSMRA80A01D612X', 'ruolo' => Member::RUOLO_VOLONTARIO]);

        $this->actingAs($this->admin(), 'admin')->get('/admin')->assertOk()
            ->assertSee('Membri attivi')->assertSee('Senza email')->assertSee('Soci entrati (30 giorni)');

        $editor = $this->admin('editor', ['comunicazioni_soci' => ['read', 'write']]);
        $this->actingAs($editor, 'admin')->get('/admin')->assertOk()
            ->assertSee('Comunicazioni pubblicate')
            ->assertDontSee('Membri attivi')->assertDontSee('Soci entrati (30 giorni)');
    }

    public function test_da_fare_compare_solo_con_richieste_in_attesa_e_a_chi_le_gestisce(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->get('/admin')->assertDontSee('Da fare');

        CommitteeInfo::current()->update(['area_soci_attiva' => true]);
        Persona::create(['nome' => 'Luca', 'cognome' => 'Verdi', 'codice_fiscale' => 'VRDLCU80A01D612X', 'email' => 'l@example.test'])
            ->forceFill(['richiesta_cancellazione_at' => now()])->save();
        Member::create(['cognome' => 'Neri', 'nome' => 'Sara', 'codice_fiscale' => 'NRESRA80A41D612X', 'ruolo' => Member::RUOLO_DIPENDENTE])
            ->forceFill(['richiesta_disattivazione_at' => now()])->save();

        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()
            ->assertSee('Da fare')
            ->assertSee('Richieste di cancellazione dei dati')->assertSee('Richieste di disattivazione dei soci');

        // Corsi in sola lettura: niente Persone, quindi nessuna richiesta di cancellazione.
        $lettore = $this->admin('editor', ['corsi' => ['read']]);
        $this->actingAs($lettore, 'admin')->get('/admin')->assertOk()->assertDontSee('Da fare');
    }
}
