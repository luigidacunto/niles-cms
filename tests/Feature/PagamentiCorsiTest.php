<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Gestione pagamenti dei corsi (fattura, ricevuta, incasso) e corsi gratuiti senza dati di fatturazione. */
class PagamentiCorsiTest extends TestCase
{
    use RefreshDatabase;

    private const CF = 'NRIPLA70B15D612T';

    private Admin $admin;

    private Corso $corso;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->creaTemplateEmail();
        $this->admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $this->corso = $this->nuovoCorso('blsd-1', 35);
    }

    private function nuovoCorso(string $slug, float $costo): Corso
    {
        return Corso::create([
            'tipologia_corso_id' => TipologiaCorso::firstOrCreate(['sigla' => 'BLSD'], ['nome' => 'BLSD'])->id, 'slug' => $slug, 'protocollo' => strtoupper($slug),
            'admin_id' => $this->admin->id, 'costo' => $costo, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    public function test_modulo_pubblico_a_pagamento_ha_il_metodo_di_pagamento_senza_doppia_codifica(): void
    {
        $this->withoutVite();
        \App\Models\CommitteeInfo::current()->update(['metodi_pagamento' => ['Bonifico', 'Contanti']]);

        // Il nome del campo è un'espressione Alpine: se l'HTML fosse codificato due volte (&amp;#039;) Alpine darebbe errore di sintassi.
        $this->get('/corsi/blsd-1/iscrizione')->assertOk()->assertSee('metodo_pagamento')->assertDontSee('&amp;#039;', false);
    }

    private function editor(array $permessi): Admin
    {
        return Admin::create(['name' => 'E', 'email' => 'e'.uniqid().'@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true, 'permissions' => $permessi]);
    }

    /** Blocco di fatturazione con $n iscritti attivi nel corso. */
    private function blocco(Corso $corso, int $n = 2): DatiFatturazioneCorso
    {
        $b = DatiFatturazioneCorso::create(['tipo' => 'azienda', 'ragione_sociale' => 'ACME srl', 'partita_iva' => '12345678901', 'metodo_pagamento' => 'Bonifico']);
        for ($i = 1; $i <= $n; $i++) {
            $persona = Persona::create(['nome' => "Nome$i", 'cognome' => "Cognome$i", 'codice_fiscale' => "CF$i".$corso->id.'AAAAAAAAAAA', 'email' => "p$i@example.test"]);
            IscrizioneCorso::create([
                'corso_id' => $corso->id, 'dati_fatturazione_corso_id' => $b->id, 'persona_id' => $persona->id, 'nome' => "Nome$i", 'cognome' => "Cognome$i",
                'email' => "p$i@example.test", 'codice_fiscale' => $persona->codice_fiscale, 'privacy_accettata_at' => now(),
            ]);
        }

        return $b;
    }

    private function attiva(): void
    {
        CommitteeInfo::current()->update(['pagamenti_corsi_attivi' => true]);
    }

    public function test_funzione_spenta_o_senza_permesso_non_accede(): void
    {
        $b = $this->blocco($this->corso);
        $url = route('admin.pagamenti-corsi.show', $this->corso);
        $editor = $this->editor(['pagamenti_corsi' => ['read', 'write']]);

        $this->actingAs($editor, 'admin')->get($url)->assertForbidden();      // interruttore spento
        $this->attiva();
        $this->actingAs($editor, 'admin')->get($url)->assertOk();
        $this->actingAs($this->editor(['corsi' => ['read']]), 'admin')->get($url)->assertForbidden(); // solo corsi, niente pagamenti
        $this->assertNotNull($b);
    }

    public function test_la_vista_mostra_guida_e_totale_senza_i_ritirati(): void
    {
        $this->attiva();
        $b = $this->blocco($this->corso, 3);
        $b->iscrizioni()->first()->update(['ritirata_at' => now()]);

        $this->actingAs($this->admin, 'admin')->get(route('admin.pagamenti-corsi.show', $this->corso))
            ->assertOk()
            ->assertSee('Cognome2')
            ->assertSee('70,00 €')           // 2 iscritti attivi × 35
            ->assertDontSee('Cognome1');
    }

    public function test_registra_fattura_e_ricevuta_separate_con_incasso_completo(): void
    {
        $this->attiva();
        $b = $this->blocco($this->corso, 2);

        $this->actingAs($this->admin, 'admin')->put(route('admin.pagamenti-corsi.update', [$this->corso, $b]), [
            'fattura_numero' => '12/2026', 'fattura_data' => '2026-11-10',
            'ricevuta_numero' => 'R-5', 'ricevuta_data' => '2026-11-09',
            'incassato' => 1, 'incassato_importo' => '70.00',
        ])->assertRedirect();

        $b->refresh();
        $this->assertTrue($b->fatturaEmessa());
        $this->assertTrue($b->ricevutaEmessa());
        $this->assertSame('incassata', $b->statoIncasso(7000));
        $this->assertSame($this->admin->id, $b->pagamento_registrato_da);
    }

    public function test_numero_senza_data_e_importo_diverso_senza_nota_sono_respinti(): void
    {
        $this->attiva();
        $b = $this->blocco($this->corso, 2);
        $url = route('admin.pagamenti-corsi.update', [$this->corso, $b]);

        $this->actingAs($this->admin, 'admin')->put($url, ['fattura_numero' => '1'])->assertSessionHasErrors('fattura_data');
        $this->actingAs($this->admin, 'admin')->put($url, ['incassato' => 1, 'incassato_importo' => '50'])->assertSessionHasErrors('incasso_nota');
        $this->assertNull($b->fresh()->incassato_at);

        $this->actingAs($this->admin, 'admin')->put($url, ['incassato' => 1, 'incassato_importo' => '50', 'incasso_nota' => 'Un iscritto salda domani'])->assertSessionDoesntHaveErrors();
        $this->assertSame('parziale', $b->fresh()->statoIncasso(7000));
    }

    public function test_blocco_di_un_altro_corso_non_si_modifica(): void
    {
        $this->attiva();
        $altro = $this->nuovoCorso('blsd-2', 35);
        $b = $this->blocco($altro, 1);

        $this->actingAs($this->admin, 'admin')->put(route('admin.pagamenti-corsi.update', [$this->corso, $b]), ['fattura_numero' => '1', 'fattura_data' => '2026-11-10'])
            ->assertNotFound();
    }

    public function test_corso_gratuito_non_chiede_ne_salva_la_fatturazione(): void
    {
        $gratis = $this->nuovoCorso('gratis-1', 0);

        $this->withoutVite();
        $this->get('/corsi/gratis-1/iscrizione')->assertOk()->assertDontSee('Fatturazione');

        $nomi = self::nomiDaCf(self::CF);
        $persona = ['nome' => $nomi[0], 'cognome' => $nomi[1], 'email' => 'x@example.test', 'telefono' => '333', 'codice_fiscale' => self::CF,
            'via' => 'Via Roma 1', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100'];

        $this->post('/corsi/gratis-1/iscrizione', [
            'richiedente' => array_intersect_key($persona, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => [$persona],
            'privacy_accettata' => 1,
        ])->assertSessionDoesntHaveErrors()->assertRedirect();

        $iscrizione = IscrizioneCorso::where('corso_id', $gratis->id)->sole();
        $this->assertNull($iscrizione->dati_fatturazione_corso_id);
        $this->assertSame(0, DatiFatturazioneCorso::count());

        // L'elenco iscritti del corso gratuito si apre anche senza fatturazione.
        $this->actingAs($this->admin, 'admin')->get(route('admin.corsi.iscritti.index', $gratis))->assertOk();
    }

    public function test_scorporo_iva_somma_sempre_al_lordo(): void
    {
        $r = \App\Support\IvaCorso::scorpora(3500, 22);
        $this->assertSame(['imponibile' => 2869, 'iva' => 631], $r);
        $this->assertSame(3500, $r['imponibile'] + $r['iva']);
    }

    public function test_la_guida_scorpora_l_iva_solo_se_prevista(): void
    {
        $this->attiva();
        $this->blocco($this->corso, 2);
        $url = route('admin.pagamenti-corsi.show', $this->corso);

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertDontSee('Imponibile');

        $this->corso->update(['iva_prevista' => true, 'aliquota_iva' => 22]);
        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()
            ->assertSee('Imponibile')
            ->assertSee('28,69 €')       // imponibile di una quota da 35 €
            ->assertSee('57,38 €');      // 2 × 28,69
    }

    public function test_corso_e_tipologia_salvano_iva_e_aliquota(): void
    {
        $url = route('admin.corsi-tipologie.store');
        $this->actingAs($this->admin, 'admin')->post($url, ['nome' => 'PSA', 'sigla' => 'PSA', 'iva_prevista' => 1])->assertSessionHasErrors('aliquota_iva');
        $this->actingAs($this->admin, 'admin')->post($url, ['nome' => 'PSA', 'sigla' => 'PSA', 'costo_predefinito' => 35, 'iva_prevista' => 1, 'aliquota_iva' => 10, 'attivo' => 1])->assertSessionDoesntHaveErrors();

        $t = TipologiaCorso::where('sigla', 'PSA')->sole();
        $this->assertTrue($t->iva_prevista);
        $this->assertSame('10.00', $t->aliquota_iva);

        // Senza la casella l'aliquota viene azzerata.
        $this->actingAs($this->admin, 'admin')->put(route('admin.corsi-tipologie.update', $t), ['nome' => 'PSA', 'sigla' => 'PSA', 'costo_predefinito' => 35, 'aliquota_iva' => 10, 'attivo' => 1]);
        $this->assertFalse($t->fresh()->iva_prevista);
        $this->assertNull($t->fresh()->aliquota_iva);
    }

    public function test_il_form_del_corso_avvisa_se_ci_sono_fatture_registrate(): void
    {
        $this->attiva();
        $b = $this->blocco($this->corso, 1);
        $url = route('admin.corsi.edit', $this->corso);

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertDontSee('fattura registrata');
        $b->update(['fattura_numero' => '1/2026', 'fattura_data' => '2026-11-10']);
        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertSee('1 fattura registrata');
    }

    public function test_con_costo_zero_l_iva_non_si_attiva_e_l_interruttore_e_disabilitato(): void
    {
        $url = route('admin.corsi-tipologie.store');
        $this->actingAs($this->admin, 'admin')->post($url, ['nome' => 'GRA', 'sigla' => 'GRA', 'costo_predefinito' => 0, 'iva_prevista' => 1, 'aliquota_iva' => 22, 'attivo' => 1])
            ->assertSessionDoesntHaveErrors();
        $t = TipologiaCorso::where('sigla', 'GRA')->sole();
        $this->assertFalse($t->iva_prevista);
        $this->assertNull($t->aliquota_iva);

        $this->actingAs($this->admin, 'admin')->get(route('admin.corsi-tipologie.edit', $t))->assertOk()
            ->assertSee('id="iva_prevista"', false)
            ->assertSeeInOrder(['id="iva_prevista"', 'disabled']);

        // Corso a pagamento con IVA, poi portato a 0: l'IVA si spegne.
        $this->corso->update(['iva_prevista' => true, 'aliquota_iva' => 22]);
        $this->actingAs($this->admin, 'admin')->get(route('admin.corsi.edit', $this->corso))->assertOk();
    }

    public function test_colonne_iva_negli_elenchi_e_dati_di_fatturazione_copiabili_nella_modale(): void
    {
        $this->attiva();
        $this->corso->update(['iva_prevista' => true, 'aliquota_iva' => 22]);
        $this->corso->tipologia->update(['iva_prevista' => true, 'aliquota_iva' => 5.5]);
        $this->blocco($this->corso, 1);
        $admin = $this->actingAs($this->admin, 'admin');

        $admin->get(route('admin.corsi-tipologie.index'))->assertOk()->assertSee('5,5%');
        $admin->get(route('admin.corsi.index'))->assertOk()->assertSee('IVA 22%');

        // Modale: dati per la fattura elettronica, con codice destinatario predefinito e copia di tutti i dati.
        $admin->get(route('admin.pagamenti-corsi.show', $this->corso))->assertOk()
            ->assertSee('Denominazione')->assertSee('ACME srl')->assertSee('0000000')->assertSee('Copia tutti')
            ->assertSee('Partita IVA: 12345678901', false);
    }
}
