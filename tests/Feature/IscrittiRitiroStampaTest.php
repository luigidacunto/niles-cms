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

/** Elenco iscritti di un corso: ritiro/ripristino senza cancellare, filtro, export per stato, elenco da stampare, pillola presenza. */
class IscrittiRitiroStampaTest extends TestCase
{
    use RefreshDatabase;

    private Corso $corso;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $this->corso = Corso::create([
            'tipologia_corso_id' => TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD'])->id, 'slug' => 'b1', 'protocollo' => 'B1',
            'admin_id' => $this->admin->id, 'costo' => 35, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    private function iscrivi(string $cf, string $nome, string $cognome, string $metodo = 'Bonifico', ?DatiFatturazioneCorso $fatt = null, ?Corso $corso = null): IscrizioneCorso
    {
        $persona = Persona::daDati(['nome' => $nome, 'cognome' => $cognome, 'codice_fiscale' => $cf, 'email' => strtolower($nome).'@example.test']);

        return IscrizioneCorso::create([
            'corso_id' => ($corso ?? $this->corso)->id,
            'dati_fatturazione_corso_id' => ($fatt ?? DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => $nome, 'cognome' => $cognome, 'metodo_pagamento' => $metodo]))->id,
            'persona_id' => $persona->id, 'nome' => $nome, 'cognome' => $cognome, 'email' => $persona->email, 'telefono' => '333',
            'codice_fiscale' => $cf, 'privacy_accettata_at' => now(),
        ]);
    }

    public function test_ritira_e_ripristina_senza_cancellare_con_filtro_di_elenco(): void
    {
        $mario = $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $anna = $this->iscrivi('VRDNNA85M41F205Z', 'Anna', 'Verdi');
        $a = $this->actingAs($this->admin, 'admin');

        $a->put(route('admin.corsi.iscritti.ritira', [$this->corso, $mario]), ['nota' => 'Non può più venire'])->assertRedirect();

        $mario->refresh();
        $this->assertTrue($mario->ritirata());
        $this->assertSame('Non può più venire', $mario->nota_ritiro);
        $this->assertSame(2, IscrizioneCorso::count());                                  // non cancellato

        // (L'avviso di conferma contiene il nome: per riconoscere le righe si usano le email.)
        $a->get(route('admin.corsi.iscritti.index', $this->corso))->assertSee('è stato ritirato dal corso')->assertSee('anna@example.test')->assertDontSee('mario@example.test')
            ->assertSee('Attivi (1)')->assertSee('Ritirati (1)');
        $a->get(route('admin.corsi.iscritti.index', [$this->corso, 'stato' => 'ritirati']))->assertSee('mario@example.test')->assertDontSee('anna@example.test')->assertSee('Non può più venire');
        $a->get(route('admin.corsi.iscritti.index', [$this->corso, 'stato' => 'tutti']))->assertSee('mario@example.test')->assertSee('anna@example.test');

        $a->put(route('admin.corsi.iscritti.ripristina', [$this->corso, $mario]))->assertRedirect();
        $this->assertFalse($mario->fresh()->ritirata());
        $this->assertNull($mario->fresh()->nota_ritiro);
    }

    public function test_permessi_e_iscrizione_di_un_altro_corso(): void
    {
        $mario = $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $altro = Corso::create(['tipologia_corso_id' => $this->corso->tipologia_corso_id, 'slug' => 'b2', 'protocollo' => 'B2', 'admin_id' => $this->admin->id,
            'costo' => 0, 'pubblicato' => true, 'data_inizio' => now()->addDays(9), 'data_fine' => now()->addDays(9)->addHours(4)]);
        $lettore = Admin::create(['name' => 'L', 'email' => 'l@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true, 'permissions' => ['corsi' => ['read']]]);

        $this->actingAs($lettore, 'admin')->put(route('admin.corsi.iscritti.ritira', [$this->corso, $mario]))->assertForbidden();
        $this->actingAs($lettore, 'admin')->get(route('admin.corsi.iscritti.index', $this->corso))->assertOk()->assertDontSee('Ritira dal corso');
        $this->actingAs($this->admin, 'admin')->put(route('admin.corsi.iscritti.ritira', [$altro, $mario]))->assertNotFound();   // non è di quel corso
        $this->assertFalse($mario->fresh()->ritirata());
    }

    public function test_chi_si_e_ritirato_puo_iscriversi_di_nuovo_e_il_ripristino_non_crea_doppioni(): void
    {
        Mail::fake();
        $mario = $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $a = $this->actingAs($this->admin, 'admin');
        $a->put(route('admin.corsi.iscritti.ritira', [$this->corso, $mario]));

        // Reinserito a mano: ora non è un doppione perché la precedente iscrizione è ritirata.
        $a->post(route('admin.corsi.iscritti.store', $this->corso), [
            'nominativo' => ['nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'mario@example.test', 'telefono' => '333', 'codice_fiscale' => 'RSSMRA80A01H501U',
                'via' => 'Via Roma 1', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100'],
            'fatturazione_modo' => 'stessi', 'fatturazione' => ['metodo_pagamento' => 'Contanti'], 'privacy_confermata' => 1,
        ])->assertRedirect();
        $this->assertSame(2, IscrizioneCorso::count());

        // Ripristinare la vecchia ora creerebbe un secondo iscritto attivo: negato.
        $a->put(route('admin.corsi.iscritti.ripristina', [$this->corso, $mario]))->assertSessionHasErrors('ripristina');
        $this->assertTrue($mario->fresh()->ritirata());
    }

    public function test_presenza_con_data_nel_tooltip_e_non_nella_pillola(): void
    {
        $mario = $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $mario->update(['presenza_confermata_at' => now()->setDate(2026, 10, 6)->setTime(14, 30)]);

        $r = $this->actingAs($this->admin, 'admin')->get(route('admin.corsi.iscritti.index', $this->corso));

        $r->assertSee('>Confermata</span>', false)->assertSee('title="Confermata il 06/10/2026 alle 14:30"', false);
        $r->assertDontSee('Confermata 06/10');
    }

    public function test_elenco_da_stampare_solo_iscritti_attivi_con_caselle_e_pagamento(): void
    {
        $azienda = DatiFatturazioneCorso::create(['tipo' => 'azienda', 'ragione_sociale' => 'ACME srl', 'partita_iva' => '12345678901', 'metodo_pagamento' => 'Bonifico']);
        $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi', 'Bonifico', $azienda);
        $this->iscrivi('VRDNNA85M41F205Z', 'Anna', 'Verdi', 'Bonifico', $azienda);
        $this->iscrivi('BNCLGU70B15D612T', 'Luigi', 'Bianchi', 'Contanti');
        $ritirato = $this->iscrivi('NRIPLA70B15D612T', 'Piero', 'Neri', 'POS');
        $ritirato->update(['ritirata_at' => now()]);

        $r = $this->actingAs($this->admin, 'admin')->get(route('admin.corsi.iscritti.stampa', $this->corso))->assertOk();

        $r->assertSeeInOrder(['Bianchi', 'Rossi', 'Verdi'])                    // per cognome
            ->assertSee('RSSMRA80A01H501U')->assertSee('Contanti')
            ->assertSee('pagamento di gruppo: ACME srl')                       // pagamento unico condiviso
            ->assertDontSee('Neri')->assertDontSee('POS')                      // ritirati fuori
            ->assertSee('Pagato')->assertSee('Presente')->assertSee('class="box"', false)
            ->assertSee('window.print()', false);
        $this->assertSame(6, substr_count($r->getContent(), 'class="box"'));   // 3 iscritti × (pagato + presente)

        $lettore = Admin::create(['name' => 'L', 'email' => 'l@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true, 'permissions' => ['corsi' => ['read']]]);
        $this->actingAs($lettore, 'admin')->get(route('admin.corsi.iscritti.stampa', $this->corso))->assertOk();   // la lettura basta
    }

    public function test_export_csv_segue_il_filtro_di_stato(): void
    {
        $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $this->iscrivi('VRDNNA85M41F205Z', 'Anna', 'Verdi')->update(['ritirata_at' => now()]);
        $a = $this->actingAs($this->admin, 'admin');

        $attivi = $a->get(route('admin.corsi.iscritti.export', [$this->corso, 'csv']))->streamedContent();
        $this->assertStringContainsString('Rossi', $attivi);
        $this->assertStringNotContainsString('Verdi', $attivi);

        $tutti = $a->get(route('admin.corsi.iscritti.export', [$this->corso, 'csv', 'stato' => 'tutti']))->streamedContent();
        $this->assertStringContainsString('Rossi', $tutti);
        $this->assertStringContainsString('Verdi', $tutti);
        $this->assertStringContainsString('Ritirato il', $tutti);

        $ritirati = $a->get(route('admin.corsi.iscritti.export', [$this->corso, 'csv', 'stato' => 'ritirati']))->streamedContent();
        $this->assertStringNotContainsString('Rossi', $ritirati);
        $this->assertStringContainsString('Verdi', $ritirati);
    }

    public function test_pagina_personale_di_chi_si_e_ritirato_non_chiede_presenza_ne_pagamento(): void
    {
        CommitteeInfo::current()->update(['iban' => 'IT60X0542811101000000123456']);
        $mario = $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $token = $mario->persona->token;

        $this->get(route('preferenze.show', $token))->assertSee('Presenza da confermare')->assertSee('Come pagare');

        $mario->update(['ritirata_at' => now()]);
        $this->get(route('preferenze.show', $token))
            ->assertSee('Iscrizione ritirata')->assertDontSee('Presenza da confermare')->assertDontSee('Come pagare')->assertDontSee('Conferma presenza e salva');

        $this->post(route('preferenze.consensi', $token), []);
        $this->assertNull($mario->fresh()->presenza_confermata_at);                       // la presenza non si conferma per un ritirato
    }

    public function test_elenco_corsi_conta_solo_gli_iscritti_attivi(): void
    {
        $this->iscrivi('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $this->iscrivi('VRDNNA85M41F205Z', 'Anna', 'Verdi')->update(['ritirata_at' => now()]);

        $this->assertSame(1, $this->corso->iscrizioni()->attive()->count());
        $this->assertSame(1, $this->corso->iscrizioni()->ritirate()->count());
    }
}
