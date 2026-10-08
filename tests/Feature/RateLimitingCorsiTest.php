<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\SicurezzaForm;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rate limiting del modulo di iscrizione: due soglie (minuto/ora), personalizzabili dal pannello, pagina 429 del sito. */
class RateLimitingCorsiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $tipologia = TipologiaCorso::create(['nome' => 'BLSD', 'sigla' => 'BLSD']);
        Corso::create([
            'tipologia_corso_id' => $tipologia->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1', 'admin_id' => $admin->id,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4), 'costo' => 0, 'pubblicato' => true,
        ]);
    }

    public function test_valori_predefiniti_e_personalizzati(): void
    {
        $s = SicurezzaForm::current();
        $this->assertSame(5, $s->limite('invii_minuto'));
        $this->assertSame(40, $s->limite('invii_ora'));
        $this->assertSame(30, $s->limite('visite_minuto'));
        $this->assertSame(300, $s->limite('visite_ora'));

        $s->update(['limite_invii_minuto' => 9]);
        $this->assertSame(9, $s->fresh()->limite('invii_minuto'));
    }

    public function test_apertura_pagina_oltre_la_soglia_al_minuto_mostra_la_pagina_429_del_sito(): void
    {
        SicurezzaForm::current()->update(['limite_visite_minuto' => 3]);

        foreach (range(1, 3) as $_) {
            $this->get('/corsi/blsd-1/iscrizione')->assertOk();
        }

        $this->get('/corsi/blsd-1/iscrizione')
            ->assertStatus(429)
            ->assertSee('Troppi tentativi ravvicinati')
            ->assertSee('Riprova tra')
            ->assertSee('Torna alla home'); // layout del sito, non la pagina generica
    }

    public function test_invii_del_modulo_limitati_anche_se_respinti_dalla_validazione(): void
    {
        SicurezzaForm::current()->update(['limite_invii_minuto' => 2]);

        $this->post('/corsi/blsd-1/iscrizione', [])->assertStatus(302);
        $this->post('/corsi/blsd-1/iscrizione', [])->assertStatus(302);
        $this->post('/corsi/blsd-1/iscrizione', [])->assertStatus(429);
    }

    public function test_le_due_finestre_hanno_contatori_indipendenti(): void
    {
        // Minuto largo, ora stretta: deve scattare la soglia oraria (chiavi distinte :min / :ora).
        SicurezzaForm::current()->update(['limite_visite_minuto' => 100, 'limite_visite_ora' => 2]);

        $this->get('/corsi/blsd-1/iscrizione')->assertOk();
        $this->get('/corsi/blsd-1/iscrizione')->assertOk();
        $this->get('/corsi/blsd-1/iscrizione')->assertStatus(429);
    }

    public function test_limite_spento_non_blocca(): void
    {
        SicurezzaForm::current()->update(['rate_limiting_attivo' => false, 'limite_visite_minuto' => 1]);

        foreach (range(1, 5) as $_) {
            $this->get('/corsi/blsd-1/iscrizione')->assertOk();
        }
    }

    public function test_pannello_salva_soglie_e_vuoto_torna_al_predefinito(): void
    {
        $admin = Admin::first();

        $this->actingAs($admin, 'admin')->put(route('admin.sicurezza-form.update'), [
            'rate_limiting_attivo' => 1,
            'limite_invii_minuto' => 8, 'limite_invii_ora' => '', 'limite_visite_minuto' => 20, 'limite_visite_ora' => '',
        ])->assertRedirect();

        $s = SicurezzaForm::current();
        $this->assertSame(8, $s->limite('invii_minuto'));
        $this->assertSame(40, $s->limite('invii_ora'));     // vuoto = predefinito
        $this->assertNull($s->limite_invii_ora);
        $this->assertSame(20, $s->limite('visite_minuto'));
    }

    public function test_pannello_rifiuta_soglie_fuori_intervallo(): void
    {
        $this->actingAs(Admin::first(), 'admin')->put(route('admin.sicurezza-form.update'), [
            'limite_invii_minuto' => 0, 'limite_visite_ora' => 5000,
        ])->assertSessionHasErrors(['limite_invii_minuto', 'limite_visite_ora']);
    }
}
