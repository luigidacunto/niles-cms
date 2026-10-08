<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\TipologiaCorso;
use App\Support\CodiceFiscaleInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Persone, referente, consensi (promemoria/newsletter) e blocco minorenni nel modulo di iscrizione ai corsi.
 *
 */
class IscrizioneConsensiTest extends TestCase
{
    use RefreshDatabase;

    // Codici fiscali di prova con struttura valida (il controllo è di formato, non del carattere di controllo).
    private const ADULTO_A = 'RSSMRA80A01H501U';   // 01/01/1980

    private const ADULTO_B = 'NRIPLA70B15D612T';   // 15/02/1970

    private const ADULTA_C = 'VRDLGI85M41F205Z';   // donna, 01/08/1985 (giorno 41 = 1 + 40)

    private const MINORE = 'BNCGLI15A01H501X';     // 01/01/2015

    private Corso $corso;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $tipologia = TipologiaCorso::create(['nome' => 'BLSD', 'sigla' => 'BLSD']);
        $this->corso = Corso::create([
            'tipologia_corso_id' => $tipologia->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1', 'admin_id' => $admin->id,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4), 'costo' => 0, 'pubblicato' => true,
        ]);
    }

    private function persona(string $cf, string $nome, string $email): array
    {
        return [
            'nome' => $nome, 'cognome' => 'Test', 'email' => $email, 'telefono' => '333', 'codice_fiscale' => $cf,
            'via' => 'Via Roma 1', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100',
        ];
    }

    /** Payload del modulo pubblico: $referente = chi compila, $nominativi = chi si iscrive. */
    private function payload(array $referente, array $nominativi, array $extra = []): array
    {
        return array_merge([
            'richiedente' => array_intersect_key($referente, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => $nominativi,
            'fatturazione_unica' => 1,
            'fatturazione' => ['tipo' => 'privato', 'nome' => 'Mario', 'cognome' => 'Rossi', 'metodo_pagamento' => 'Bonifico'],
            'privacy_accettata' => 1,
        ], $extra);
    }

    private function invia(array $payload)
    {
        return $this->post('/corsi/blsd-1/iscrizione', $payload);
    }

    public function test_solo_io_con_le_due_spunte(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test');

        $this->invia($this->payload($a, [$a], ['consenso_promemoria' => 1, 'consenso_newsletter' => 1]))
            ->assertRedirect(route('corsi.iscrizione.confermata', $this->corso));

        $p = Persona::firstWhere('codice_fiscale', self::ADULTO_A);
        $this->assertSame('confermato', $p->newsletter_stato);
        $this->assertSame('confermato', $p->promemoria_stato);
        $this->assertSame(2, $p->consensi()->count());
        $this->assertSame(64, strlen($p->token));
        $this->assertNull(IscrizioneCorso::first()->referente_persona_id);
        $this->assertStringContainsString('newsletter', $p->consensi()->where('finalita', 'newsletter')->value('testo'));
    }

    public function test_senza_spunte_nessun_consenso(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test');

        $this->invia($this->payload($a, [$a]))->assertRedirect();

        $p = Persona::first();
        $this->assertNull($p->newsletter_stato);
        $this->assertNull($p->promemoria_stato);
        $this->assertSame(0, $p->consensi()->count());
    }

    public function test_referente_che_non_frequenta_iscrive_altri(): void
    {
        $hr = $this->persona(self::ADULTO_A, 'Hilda', 'hr@example.test');
        $b = $this->persona(self::ADULTO_B, 'Bruno', 'bruno@example.test');
        $c = $this->persona(self::ADULTA_C, 'Carla', 'carla@example.test');

        $this->invia($this->payload($hr, [$b, $c], ['autodichiarazione_terzi' => 1, 'consenso_newsletter' => 1, 'consenso_promemoria' => 1]))
            ->assertRedirect();

        $referente = Persona::firstWhere('codice_fiscale', self::ADULTO_A);
        $this->assertSame(3, Persona::count());
        $this->assertSame('confermato', $referente->newsletter_stato);
        // Non frequenta: il promemoria dell'attestato non è suo e la spunta viene ignorata.
        $this->assertNull($referente->promemoria_stato);

        $iscrizioni = IscrizioneCorso::all();
        $this->assertCount(2, $iscrizioni);
        $this->assertTrue($iscrizioni->every(fn ($i) => $i->referente_persona_id === $referente->id));
        // Le persone iscritte da altri non ricevono consensi dal modulo.
        $this->assertNull(Persona::firstWhere('codice_fiscale', self::ADULTO_B)->newsletter_stato);
    }

    public function test_referente_che_frequenta_anche_lui_e_la_stessa_persona(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Anna', 'anna@example.test');
        $b = $this->persona(self::ADULTO_B, 'Bruno', 'bruno@example.test');

        $this->invia($this->payload($a, [$a, $b], ['autodichiarazione_terzi' => 1, 'consenso_promemoria' => 1]))->assertRedirect();

        $this->assertSame(2, Persona::count());
        $referente = Persona::firstWhere('codice_fiscale', self::ADULTO_A);
        $sua = IscrizioneCorso::firstWhere('persona_id', $referente->id);
        $altra = IscrizioneCorso::firstWhere('codice_fiscale', self::ADULTO_B);

        $this->assertNull($sua->referente_persona_id);
        $this->assertSame($referente->id, $altra->referente_persona_id);
        $this->assertSame('confermato', $referente->promemoria_stato);
    }

    public function test_autodichiarazione_obbligatoria_se_si_iscrive_qualcun_altro(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Anna', 'anna@example.test');
        $b = $this->persona(self::ADULTO_B, 'Bruno', 'bruno@example.test');

        $this->invia($this->payload($a, [$a, $b]))->assertSessionHasErrors('autodichiarazione_terzi');
        $this->assertSame(0, IscrizioneCorso::count());
    }

    public function test_minorenne_bloccato_tra_i_nominativi_e_come_referente(): void
    {
        $adulto = $this->persona(self::ADULTO_A, 'Anna', 'anna@example.test');
        $minore = $this->persona(self::MINORE, 'Gigi', 'gigi@example.test');

        // Genitore che prova a iscrivere il figlio.
        $this->invia($this->payload($adulto, [$minore], ['autodichiarazione_terzi' => 1]))
            ->assertSessionHasErrors('nominativi.0.codice_fiscale');

        // Minorenne che compila come referente per un adulto.
        $this->invia($this->payload($minore, [$adulto], ['autodichiarazione_terzi' => 1]))
            ->assertSessionHasErrors('richiedente.codice_fiscale');

        $this->assertSame(0, IscrizioneCorso::count());
        $this->assertSame(0, Persona::count());
    }

    public function test_stessa_persona_non_si_iscrive_due_volte_allo_stesso_corso(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test');
        $b = $this->persona(self::ADULTO_B, 'Bruno', 'bruno@example.test');

        $this->invia($this->payload($a, [$a]))->assertRedirect(route('corsi.iscrizione.confermata', $this->corso));

        // Già iscritta (anche con il codice fiscale in minuscolo).
        $stesso = $this->persona(strtolower(self::ADULTO_A), 'Mario', 'mario@example.test');
        $this->invia($this->payload($a, [$stesso]))->assertSessionHasErrors('nominativi.0.codice_fiscale');

        // Ripetuta nello stesso invio.
        $this->invia($this->payload($a, [$b, $b], ['autodichiarazione_terzi' => 1]))->assertSessionHasErrors('nominativi.1.codice_fiscale');

        $this->assertSame(1, IscrizioneCorso::count());
    }

    public function test_iscrizione_a_mano_non_crea_doppioni(): void
    {
        Mail::fake();
        $payload = [
            'nominativo' => $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test'),
            'fatturazione_modo' => 'stessi', 'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_confermata' => 1,
        ];
        $admin = Admin::first();

        $this->actingAs($admin, 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), $payload)->assertRedirect(route('admin.corsi.iscritti.index', $this->corso));
        $this->actingAs($admin, 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), $payload)->assertSessionHasErrors('nominativo.codice_fiscale');

        $this->assertSame(1, IscrizioneCorso::count());
    }

    public function test_codice_fiscale_non_valido_respinto(): void
    {
        $a = $this->persona('AAAAAAAAAAAAAAAA', 'Anna', 'anna@example.test');

        $this->invia($this->payload($a, [$a]))->assertSessionHasErrors('nominativi.0.codice_fiscale');
    }

    public function test_fatturazione_azienda_con_codice_destinatario_e_pec(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Anna', 'anna@example.test');
        $azienda = ['tipo' => 'azienda', 'ragione_sociale' => 'ACME srl', 'partita_iva' => '12345678901',
            'codice_destinatario' => 'abc1234', 'pec' => 'acme@pec.example.test', 'metodo_pagamento' => 'Bonifico'];

        $this->invia($this->payload($a, [$a], ['fatturazione' => $azienda]))->assertRedirect();

        $f = IscrizioneCorso::first()->datiFatturazione;
        $this->assertSame('ABC1234', $f->codice_destinatario);
        $this->assertSame('acme@pec.example.test', $f->pec);
    }

    public function test_fatturazione_formato_codice_destinatario_e_pec(): void
    {
        $a = $this->persona(self::ADULTO_A, 'Anna', 'anna@example.test');
        $azienda = ['tipo' => 'azienda', 'ragione_sociale' => 'ACME', 'partita_iva' => '12345678901',
            'codice_destinatario' => 'TROPPO-LUNGO!', 'pec' => 'non-una-mail', 'metodo_pagamento' => 'Bonifico'];

        $this->invia($this->payload($a, [$a], ['fatturazione' => $azienda]))
            ->assertSessionHasErrors(['fatturazione.codice_destinatario', 'fatturazione.pec']);

        // Facoltativi: vuoti vanno bene.
        unset($azienda['codice_destinatario'], $azienda['pec']);
        $this->invia($this->payload($a, [$a], ['fatturazione' => $azienda]))->assertRedirect();
    }

    public function test_iscrizione_manuale_admin_non_registra_consensi(): void
    {
        $admin = Admin::first();
        $a = $this->persona(self::MINORE, 'Gigi', 'gigi@example.test'); // da admin anche un minorenne (segreteria)

        $this->actingAs($admin, 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), [
            'nominativo' => $a,
            'fatturazione_modo' => 'stessi',
            'fatturazione' => ['metodo_pagamento' => 'Bonifico'],
            'privacy_confermata' => 1,
            'consenso_newsletter' => 1, // non più supportata: ignorata
        ])->assertRedirect();

        $p = Persona::firstWhere('codice_fiscale', self::MINORE);
        $this->assertNotNull($p);
        $this->assertNull($p->newsletter_stato);
        $this->assertSame(0, $p->consensi()->count());
    }

    public function test_registra_consenso_stato_storico_e_revoca(): void
    {
        $p = Persona::daDati(['nome' => 'A', 'cognome' => 'B', 'codice_fiscale' => self::ADULTO_A, 'email' => 'a@example.test']);

        $p->registraConsenso('newsletter', 'richiesto', 'form_pubblico');
        $this->assertSame('richiesto', $p->fresh()->newsletter_stato);
        $this->assertFalse($p->fresh()->haConsenso('newsletter'));

        $p->registraConsenso('newsletter', 'confermato', 'link_personale');
        $this->assertTrue($p->fresh()->haConsenso('newsletter'));
        $this->assertSame(1, Persona::conConsenso('newsletter')->count());

        // Un nuovo "richiesto" non declassa un consenso già confermato.
        $p->registraConsenso('newsletter', 'richiesto', 'form_pubblico');
        $this->assertSame('confermato', $p->fresh()->newsletter_stato);
        $this->assertSame(2, $p->consensi()->count());

        $p->registraConsenso('newsletter', 'revocato', 'link_personale');
        $this->assertSame('revocato', $p->fresh()->newsletter_stato);
        $this->assertSame(0, Persona::conConsenso('newsletter')->count());
        $this->assertSame(['richiesto', 'confermato', 'revocato'], $p->consensi()->orderBy('id')->pluck('evento')->all());

        $this->expectException(\InvalidArgumentException::class);
        $p->registraConsenso('altro', 'confermato', 'form_pubblico');
    }

    public function test_daDati_non_tocca_i_consensi_e_aggiorna_i_contatti(): void
    {
        $p = Persona::daDati(['nome' => 'A', 'cognome' => 'B', 'codice_fiscale' => self::ADULTO_A, 'email' => 'vecchia@example.test']);
        $p->registraConsenso('newsletter', 'confermato', 'form_pubblico');

        $q = Persona::daDati(['nome' => 'A', 'cognome' => 'B', 'codice_fiscale' => strtolower(self::ADULTO_A), 'email' => 'nuova@example.test']);

        $this->assertSame($p->id, $q->id);
        $this->assertSame('nuova@example.test', $q->fresh()->email);
        $this->assertSame('confermato', $q->fresh()->newsletter_stato);
        $this->assertSame(1, Persona::count());
    }

    public function test_data_nascita_dal_codice_fiscale(): void
    {
        $this->assertSame('1980-01-01', CodiceFiscaleInfo::dataNascita(self::ADULTO_A)->toDateString());
        $this->assertSame('1985-08-01', CodiceFiscaleInfo::dataNascita(self::ADULTA_C)->toDateString()); // +40 donna
        $this->assertSame('2015-01-01', CodiceFiscaleInfo::dataNascita(self::MINORE)->toDateString());
        // Omocodia: cifre dell'anno/giorno sostituite da lettere (8L = 80, LM = 01).
        $this->assertSame('1980-01-01', CodiceFiscaleInfo::dataNascita('RSSMRA8LALMH501U')->toDateString());
        $this->assertNull(CodiceFiscaleInfo::dataNascita('AAAAAAAAAAAAAAAA'));
        $this->assertNull(CodiceFiscaleInfo::dataNascita('RSSMRA80A32H501U')); // giorno 32
        $this->assertTrue(CodiceFiscaleInfo::maggiorenne(self::ADULTO_A));
        $this->assertFalse(CodiceFiscaleInfo::maggiorenne(self::MINORE));
    }

    public function test_scadenza_attestato_per_tipo_di_validita(): void
    {
        $mesi = new TipologiaCorso(['validita_tipo' => 'mesi', 'validita_valore' => 24]);
        $fineAnno = new TipologiaCorso(['validita_tipo' => 'fine_anno', 'validita_valore' => 2]);
        $nessuna = new TipologiaCorso;
        $corso = now()->setDate(2026, 10, 10);

        $this->assertSame('2028-10-10', $mesi->scadenzaDa($corso)->toDateString());
        $this->assertSame('2028-12-31', $fineAnno->scadenzaDa($corso)->toDateString());
        $this->assertNull($nessuna->scadenzaDa($corso));
        $this->assertSame('Non scade', $nessuna->validitaLabel());
        // Fine mese: 31/08 + 6 mesi non sfora (28/02).
        $this->assertSame('2027-02-28', (new TipologiaCorso(['validita_tipo' => 'mesi', 'validita_valore' => 6]))->scadenzaDa(now()->setDate(2026, 8, 31))->toDateString());
    }
}
