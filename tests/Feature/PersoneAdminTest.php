<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pannello Persone: elenco con filtri, revoca, export CSV newsletter, anonimizzazione (solo admin, fatturazione conservata). */
class PersoneAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', array $permessi = []): Admin
    {
        return Admin::create(['name' => ucfirst($role), 'email' => $role.rand(1, 9999).'@example.test', 'password' => 'x', 'role' => $role, 'active' => true, 'permissions' => $permessi]);
    }

    private function persona(string $cf, string $nome, string $cognome, ?string $email = null): Persona
    {
        return Persona::daDati(['nome' => $nome, 'cognome' => $cognome, 'codice_fiscale' => $cf, 'email' => $email ?? strtolower($nome).'@example.test', 'telefono' => '333']);
    }

    private function iscrivi(Persona $p, ?Corso $corso = null): IscrizioneCorso
    {
        $corso ??= $this->corso ??= Corso::create([
            'tipologia_corso_id' => TipologiaCorso::create(['nome' => 'BLSD', 'sigla' => 'BLSD'])->id, 'slug' => 'b1', 'protocollo' => 'B1',
            'admin_id' => Admin::first()->id, 'costo' => 35, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);

        return IscrizioneCorso::create([
            'corso_id' => $corso->id,
            'dati_fatturazione_corso_id' => DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => $p->nome, 'cognome' => $p->cognome, 'codice_fiscale' => $p->codice_fiscale, 'email' => $p->email])->id,
            'persona_id' => $p->id, 'nome' => $p->nome, 'cognome' => $p->cognome, 'email' => $p->email, 'telefono' => '333',
            'codice_fiscale' => $p->codice_fiscale, 'via' => 'Via Roma 1', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100',
            'privacy_accettata_at' => now(),
        ]);
    }

    private ?Corso $corso = null;

    public function test_elenco_con_ricerca_filtri_e_totali(): void
    {
        $admin = $this->admin();
        $mario = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $mario->registraConsenso('newsletter', 'confermato', 'form_pubblico');
        $anna = $this->persona('VRDNNA85M41F205Z', 'Anna', 'Verdi');
        $anna->registraConsenso('newsletter', 'richiesto', 'email_iscrizione');
        $this->persona('BNCLGU70B15D612T', 'Luigi', 'Bianchi');

        $base = $this->actingAs($admin, 'admin');
        $base->get(route('admin.persone.index'))->assertOk()->assertSee('Rossi')->assertSee('Verdi')->assertSee('Bianchi');
        $base->get(route('admin.persone.index', ['q' => 'verd']))->assertSee('Verdi')->assertDontSee('Rossi');
        $base->get(route('admin.persone.index', ['newsletter' => 'confermato']))->assertSee('Rossi')->assertDontSee('Verdi')->assertDontSee('Bianchi');
        $base->get(route('admin.persone.index', ['newsletter' => 'nessuno']))->assertSee('Bianchi')->assertDontSee('Rossi');
        $base->get(route('admin.persone.index', ['newsletter' => 'richiesto']))->assertSee('Verdi')->assertDontSee('Rossi');
        $base->get(route('admin.persone.index', ['newsletter' => 'inesistente']))->assertSessionHasErrors('newsletter');
    }

    public function test_elenco_paginato_da_25_con_filtri_mantenuti_nei_link(): void
    {
        $admin = $this->admin();
        foreach (range(1, 30) as $n) {
            $this->persona('RSSMR'.str_pad((string) $n, 2, '0', STR_PAD_LEFT).'A01H501U', 'Nome'.$n, 'Cognome'.str_pad((string) $n, 2, '0', STR_PAD_LEFT));
        }

        $pagina1 = $this->actingAs($admin, 'admin')->get(route('admin.persone.index', ['q' => 'Cognome']));
        $pagina1->assertOk()->assertSee('Cognome01')->assertSee('Cognome25')->assertDontSee('Cognome26');
        $pagina1->assertSee('class="pagination"', false);                              // paginazione Bootstrap (come le altre liste admin)
        $pagina1->assertSee('q=Cognome&amp;page=2', false);                            // il filtro resta nel link

        $this->actingAs($admin, 'admin')->get(route('admin.persone.index', ['q' => 'Cognome', 'page' => 2]))
            ->assertSee('Cognome26')->assertSee('Cognome30')->assertDontSee('Cognome25');
    }

    public function test_permessi_lettura_scrittura_e_senza_permesso(): void
    {
        $p = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $p->registraConsenso('newsletter', 'confermato', 'form_pubblico');
        $lettore = $this->admin('editor', ['corsi' => ['read']]);
        $scrittore = $this->admin('editor', ['corsi' => ['write']]);
        $nessuno = $this->admin('editor', []);

        $this->actingAs($lettore, 'admin')->get(route('admin.persone.index'))->assertOk();
        $this->actingAs($lettore, 'admin')->post(route('admin.persone.revoca', [$p, 'newsletter']))->assertForbidden();
        $this->actingAs($nessuno, 'admin')->get(route('admin.persone.index'))->assertForbidden();
        $this->actingAs($nessuno, 'admin')->get(route('admin.persone.esporta', ['colonne' => ['email']]))->assertForbidden();

        $this->actingAs($scrittore, 'admin')->post(route('admin.persone.revoca', [$p, 'newsletter']))->assertRedirect();
        $this->assertSame('revocato', $p->fresh()->newsletter_stato);
        $this->assertSame('admin', $p->consensi()->where('evento', 'revocato')->value('origine'));
    }

    public function test_l_admin_puo_revocare_ma_non_concedere(): void
    {
        $admin = $this->admin();
        $p = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi');

        // Nessuna scelta: la revoca non fa nulla (e il concedere non esiste come azione).
        $this->actingAs($admin, 'admin')->post(route('admin.persone.revoca', [$p, 'newsletter']))->assertRedirect();
        $this->assertNull($p->fresh()->newsletter_stato);
        $this->actingAs($admin, 'admin')->post(route('admin.persone.revoca', [$p, 'inventato']))->assertNotFound();
    }

    public function test_export_csv_solo_newsletter_confermata_con_colonne_scelte(): void
    {
        $admin = $this->admin();
        $mario = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi', 'mario@example.test');
        $mario->registraConsenso('newsletter', 'confermato', 'form_pubblico');
        $anna = $this->persona('VRDNNA85M41F205Z', 'Anna', 'Verdi');
        $anna->registraConsenso('newsletter', 'revocato', 'link_personale');
        $this->persona('BNCLGU70B15D612T', 'Luigi', 'Bianchi')->registraConsenso('promemoria', 'confermato', 'form_pubblico'); // non newsletter
        $peric = $this->persona('PRCLGU70B15D612T', '=cmd', 'Evil', 'evil@example.test');
        $peric->registraConsenso('newsletter', 'confermato', 'form_pubblico');

        $r = $this->actingAs($admin, 'admin')->get(route('admin.persone.esporta', ['colonne' => ['email', 'nome', 'consenso_dal'], 'separatore' => ',']));
        $r->assertOk()->assertDownload();
        $csv = $r->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);                      // BOM per Excel
        $this->assertStringContainsString('Email,Nome,"Consenso dato il"', $csv);
        $this->assertStringContainsString('mario@example.test,Mario,'.now()->format('d/m/Y'), $csv);
        $this->assertStringNotContainsString('Verdi', $csv);
        $this->assertStringNotContainsString('anna@example.test', $csv);          // revocato: fuori
        $this->assertStringNotContainsString('luigi@example.test', $csv);         // solo promemoria: fuori
        $this->assertStringNotContainsString('Cognome', $csv);                    // colonna non scelta
        $this->assertStringContainsString("'=cmd", $csv);                         // formule neutralizzate

        $this->actingAs($admin, 'admin')->get(route('admin.persone.esporta'))->assertSessionHasErrors('colonne');
        $this->actingAs($admin, 'admin')->get(route('admin.persone.esporta', ['colonne' => ['password']]))->assertSessionHasErrors('colonne.0');
    }

    public function test_anonimizzazione_toglie_i_dati_personali_e_conserva_fatturazione_e_presenza(): void
    {
        $admin = $this->admin();
        $p = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi', 'mario@example.test');
        $p->registraConsenso('newsletter', 'confermato', 'form_pubblico');
        $p->registraConsenso('promemoria', 'confermato', 'form_pubblico');
        $i = $this->iscrivi($p);
        $vecchioToken = $p->token;
        $altra = $this->persona('VRDNNA85M41F205Z', 'Anna', 'Verdi');
        $iscrittaDaMario = $this->iscrivi($altra);
        $iscrittaDaMario->update(['referente_persona_id' => $p->id]);

        $this->actingAs($admin, 'admin')->post(route('admin.persone.anonimizza', $p), [])->assertSessionHasErrors('conferma');
        $this->assertFalse($p->fresh()->anonimizzata());

        $this->actingAs($admin, 'admin')->post(route('admin.persone.anonimizza', $p), ['conferma' => 1])->assertRedirect(route('admin.persone.show', $p));

        $p->refresh();
        $this->assertTrue($p->anonimizzata());
        $this->assertSame('Anonimo', $p->nome);
        $this->assertSame('ANONIMO'.str_pad((string) $p->id, 9, '0', STR_PAD_LEFT), $p->codice_fiscale);
        $this->assertSame(16, strlen($p->codice_fiscale));
        $this->assertNull($p->email);
        $this->assertNull($p->cellulare);
        $this->assertNull($p->newsletter_stato);
        $this->assertNull($p->promemoria_stato);
        $this->assertSame(0, $p->consensi()->count());
        $this->assertNotSame($vecchioToken, $p->token);                              // il vecchio link non funziona più
        $this->get(route('preferenze.show', $vecchioToken))->assertNotFound();

        // La riga iscrizione resta (presenza anonima), con i dati personali anonimizzati.
        $i->refresh();
        $this->assertSame('Anonimo', $i->nome);
        $this->assertSame($p->codice_fiscale, $i->codice_fiscale);
        $this->assertNull($i->telefono);
        $this->assertNull($i->via);
        $this->assertSame($p->id, $i->persona_id);

        // I dati di fatturazione NON si toccano (fatture emesse da conservare).
        $f = $i->datiFatturazione->fresh();
        $this->assertSame('Mario', $f->nome);
        $this->assertSame('RSSMRA80A01H501U', $f->codice_fiscale);

        // Le persone che Mario aveva iscritto non sono toccate: il collegamento punta ora a una persona anonima.
        $iscrittaDaMario->refresh();
        $this->assertSame('Anna', $iscrittaDaMario->nome);
        $this->assertSame($p->id, $iscrittaDaMario->referente_persona_id);

        // Idempotente, e non finisce nelle liste di invio.
        $p->anonimizza();
        $this->assertSame(0, Persona::conConsenso('newsletter')->count());
        $this->actingAs($admin, 'admin')->get(route('admin.persone.index', ['cancellazione' => 'anonimizzate']))->assertSee('anonimizzata');
        $this->actingAs($admin, 'admin')->get(route('admin.persone.index'))->assertDontSee('ANONIMO');   // di base nascoste
    }

    public function test_anonimizzazione_solo_per_admin_e_non_revocabile_su_anonimizzata(): void
    {
        $p = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $editor = $this->admin('editor', ['corsi' => ['write']]);

        $this->actingAs($editor, 'admin')->post(route('admin.persone.anonimizza', $p), ['conferma' => 1])->assertForbidden();
        $this->assertFalse($p->fresh()->anonimizzata());

        // Scheda: l'editor non vede il pulsante di anonimizzazione.
        $this->actingAs($editor, 'admin')->get(route('admin.persone.show', $p))->assertOk()->assertDontSee('Anonimizza i dati');
        $this->actingAs($this->admin(), 'admin')->get(route('admin.persone.show', $p))->assertOk()->assertSee('Anonimizza i dati');

        $p->anonimizza();
        $this->actingAs($this->admin(), 'admin')->post(route('admin.persone.revoca', [$p, 'newsletter']))->assertNotFound();
    }

    public function test_la_richiesta_dalla_pagina_personale_compare_nel_pannello_senza_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $p = $this->persona('RSSMRA80A01H501U', 'Mario', 'Rossi');
        $p->registraConsenso('newsletter', 'confermato', 'form_pubblico');

        $this->post(route('preferenze.cancellazione', $p->token))->assertRedirect();

        \Illuminate\Support\Facades\Mail::assertNothingSent();
        $this->actingAs($this->admin(), 'admin')->get(route('admin.persone.index'))
            ->assertSee('Richieste di cancellazione dei dati in attesa (1)')->assertSee('Rossi');
        $this->actingAs($this->admin(), 'admin')->get(route('admin.persone.index', ['cancellazione' => 'in_attesa']))->assertSee('cancellazione richiesta');
        $this->actingAs($this->admin(), 'admin')->get(route('admin.persone.show', $p))->assertSee('Richiesta di cancellazione dei dati');
    }
}
