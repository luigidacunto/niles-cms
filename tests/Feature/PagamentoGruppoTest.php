<?php

namespace Tests\Feature;

use App\Mail\IscrizioneCorsoMail;
use App\Mail\RiepilogoReferenteMail;
use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\EmailTemplate;
use App\Models\IscrizioneCorso;
use App\Models\TipologiaCorso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pagamenti con più iscritti: ognuno paga per sé (indicazioni nella propria email, fatturazione sui suoi dati come privato)
 * oppure fatturazione unica (indicazioni nel riepilogo a chi ha iscritto, non agli iscritti).
 */
class PagamentoGruppoTest extends TestCase
{
    use RefreshDatabase;

    private const HR = 'BRNGPP72D10A390X';

    private const BRUNO = 'NRIPLA70B15D612T';

    private const CARLA = 'VRDLGI85M41F205Z';

    private Corso $corso;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->creaTemplateEmail();
        CommitteeInfo::current()->update(['email' => 'segreteria@comitato.test', 'iban' => 'IT60X0542811101000000123456', 'intestatario_conto' => 'Comitato Prova']);
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $this->corso = Corso::create([
            'tipologia_corso_id' => TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD'])->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1',
            'admin_id' => $admin->id, 'costo' => 35, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    private function p(string $cf, string $nome, string $email): array
    {
        return ['nome' => $nome, 'cognome' => 'Test', 'email' => $email, 'telefono' => '333', 'codice_fiscale' => $cf,
            'via' => 'Via Roma 1', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100'];
    }

    private function invia(array $referente, array $nominativi, array $fatturazione, bool $unica)
    {
        return $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => array_intersect_key($referente, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => $nominativi,
            'fatturazione_unica' => $unica ? 1 : 0,
            'fatturazione' => $unica ? $fatturazione : null,
            'privacy_accettata' => 1, 'autodichiarazione_terzi' => 1,
        ]);
    }

    private function html(IscrizioneCorsoMail|RiepilogoReferenteMail $mail): string
    {
        return $mail->render();
    }

    private function inviata(string $classe, string $a)
    {
        return Mail::sent($classe, fn ($m) => $m->hasTo($a))->first();
    }

    public function test_pagamenti_separati_ognuno_riceve_le_coordinate_e_la_fatturazione_usa_i_suoi_dati(): void
    {
        $hr = $this->p(self::HR, 'Hilda', 'hr@azienda.test');
        $bruno = $this->p(self::BRUNO, 'Bruno', 'bruno@azienda.test') + ['fatturazione' => ['metodo_pagamento' => 'Bonifico']];
        $carla = $this->p(self::CARLA, 'Carla', 'carla@azienda.test') + ['fatturazione' => ['metodo_pagamento' => 'Contanti']];

        $this->invia($hr, [$bruno, $carla], [], false)->assertRedirect()->assertSessionHasNoErrors();

        // Fatturazione: una riga per persona, privato, con i dati della persona (nessun dato extra chiesto) e il suo metodo.
        $this->assertSame(2, DatiFatturazioneCorso::count());
        $fBruno = IscrizioneCorso::firstWhere('codice_fiscale', self::BRUNO)->datiFatturazione;
        $this->assertSame('privato', $fBruno->tipo);
        $this->assertSame(['Bruno', 'Test', self::BRUNO], [$fBruno->nome, $fBruno->cognome, $fBruno->codice_fiscale]);
        $this->assertSame('bruno@azienda.test', $fBruno->email);
        $this->assertSame('Bonifico', $fBruno->metodo_pagamento);
        $this->assertSame('Contanti', IscrizioneCorso::firstWhere('codice_fiscale', self::CARLA)->datiFatturazione->metodo_pagamento);

        // Bruno (bonifico) ha le coordinate nella sua email, con la sua causale; Carla (contanti) no.
        $mailBruno = $this->html($this->inviata(IscrizioneCorsoMail::class, 'bruno@azienda.test'));
        $this->assertStringContainsString('IT60 X054 2811 1010 0000 0123 456', $mailBruno);
        $this->assertStringContainsString('Corso BLSD-1 - Test Bruno', $mailBruno);
        $this->assertStringNotContainsString('IBAN', $this->html($this->inviata(IscrizioneCorsoMail::class, 'carla@azienda.test')));

        // Riepilogo a Hilda: chi ha iscritto, ognuno paga per sé, nessuna coordinata.
        $riepilogo = $this->html($this->inviata(RiepilogoReferenteMail::class, 'hr@azienda.test'));
        $this->assertStringContainsString('2 persone', $riepilogo);
        $this->assertStringContainsString(self::BRUNO, $riepilogo);
        $this->assertStringContainsString('Ogni persona paga per sé', $riepilogo);
        $this->assertStringNotContainsString('IBAN', $riepilogo);
        $this->assertStringNotContainsString('{', $riepilogo);
    }

    public function test_fatturazione_unica_le_coordinate_vanno_a_chi_ha_iscritto_non_agli_iscritti(): void
    {
        $hr = $this->p(self::HR, 'Hilda', 'hr@azienda.test');
        $azienda = ['tipo' => 'azienda', 'ragione_sociale' => 'ACME srl', 'partita_iva' => '12345678901', 'codice_destinatario' => 'ABC1234',
            'via' => 'Via Industria 5', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100', 'metodo_pagamento' => 'Bonifico'];

        $this->invia($hr, [$this->p(self::BRUNO, 'Bruno', 'bruno@azienda.test'), $this->p(self::CARLA, 'Carla', 'carla@azienda.test')], $azienda, true)
            ->assertRedirect()->assertSessionHasNoErrors();

        foreach (['bruno@azienda.test', 'carla@azienda.test'] as $iscritto) {
            $html = $this->html($this->inviata(IscrizioneCorsoMail::class, $iscritto));
            $this->assertStringNotContainsString('IBAN', $html, "{$iscritto} non deve ricevere le coordinate");
            $this->assertStringNotContainsString('Come pagare', $html);
        }

        Mail::assertSent(RiepilogoReferenteMail::class, 1);
        $riepilogo = $this->html($this->inviata(RiepilogoReferenteMail::class, 'hr@azienda.test'));
        $this->assertStringContainsString('IT60 X054 2811 1010 0000 0123 456', $riepilogo);
        $this->assertStringContainsString('70,00 €', $riepilogo);                       // 35 × 2
        $this->assertStringContainsString('35,00 € × 2 persone', $riepilogo);
        $this->assertStringContainsString('Corso BLSD-1 - ACME srl', $riepilogo);       // causale con l'intestatario della fattura
        $this->assertStringContainsString('ACME srl', $riepilogo);
        $this->assertStringContainsString('12345678901', $riepilogo);
        $this->assertStringContainsString('ABC1234', $riepilogo);                       // codice destinatario inserito
        $this->assertStringContainsString(self::CARLA, $riepilogo);                     // elenco di chi ha iscritto
    }

    public function test_un_dipendente_pagato_dall_azienda_non_riceve_le_coordinate_e_il_referente_si(): void
    {
        $azienda = ['tipo' => 'azienda', 'ragione_sociale' => 'ACME srl', 'partita_iva' => '12345678901', 'metodo_pagamento' => 'Bonifico'];

        $this->invia($this->p(self::HR, 'Hilda', 'hr@azienda.test'), [$this->p(self::BRUNO, 'Bruno', 'bruno@azienda.test')], $azienda, true)->assertRedirect();

        $this->assertStringNotContainsString('IBAN', $this->html($this->inviata(IscrizioneCorsoMail::class, 'bruno@azienda.test')));
        $riepilogo = $this->html($this->inviata(RiepilogoReferenteMail::class, 'hr@azienda.test'));
        $this->assertStringContainsString('35,00 €', $riepilogo);
        $this->assertStringNotContainsString('×', $riepilogo);                           // una persona sola: niente "× N"
        $this->assertStringContainsString('IT60 X054', $riepilogo);
    }

    public function test_iscrizione_da_soli_una_sola_email_con_le_coordinate_e_nessun_riepilogo(): void
    {
        $io = $this->p(self::HR, 'Hilda', 'hr@azienda.test');

        $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => array_intersect_key($io, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => [$io], 'fatturazione_unica' => 1, 'fatturazione_come_iscritto' => 1,
            'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_accettata' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        Mail::assertSent(IscrizioneCorsoMail::class, 1);
        Mail::assertNotSent(RiepilogoReferenteMail::class);
        $this->assertStringContainsString('IT60 X054', $this->html($this->inviata(IscrizioneCorsoMail::class, 'hr@azienda.test')));
    }

    public function test_referente_che_frequenta_e_iscrive_altri_riceve_la_sua_email_e_il_riepilogo(): void
    {
        $hr = $this->p(self::HR, 'Hilda', 'hr@azienda.test');
        $privato = ['tipo' => 'privato', 'nome' => 'Hilda', 'cognome' => 'Test', 'metodo_pagamento' => 'Bonifico'];

        $this->invia($hr, [$hr, $this->p(self::BRUNO, 'Bruno', 'bruno@azienda.test')], $privato, true)->assertRedirect();

        Mail::assertSent(IscrizioneCorsoMail::class, fn ($m) => $m->hasTo('hr@azienda.test'));
        Mail::assertSent(RiepilogoReferenteMail::class, fn ($m) => $m->hasTo('hr@azienda.test'));
        Mail::assertSent(RiepilogoReferenteMail::class, 1);
    }

    public function test_fatturazione_unica_con_contanti_riepilogo_senza_coordinate(): void
    {
        $this->invia($this->p(self::HR, 'Hilda', 'hr@azienda.test'),
            [$this->p(self::BRUNO, 'Bruno', 'bruno@azienda.test'), $this->p(self::CARLA, 'Carla', 'carla@azienda.test')],
            ['tipo' => 'privato', 'nome' => 'Hilda', 'cognome' => 'Test', 'metodo_pagamento' => 'Contanti'], true)->assertRedirect();

        $riepilogo = $this->html($this->inviata(RiepilogoReferenteMail::class, 'hr@azienda.test'));
        $this->assertStringContainsString('Contanti', $riepilogo);
        $this->assertStringNotContainsString('IBAN', $riepilogo);
    }

    public function test_iscritto_aggiunto_a_mano_al_gruppo_non_riceve_coordinate_ne_scatta_un_nuovo_riepilogo(): void
    {
        $azienda = ['tipo' => 'azienda', 'ragione_sociale' => 'ACME srl', 'partita_iva' => '12345678901', 'metodo_pagamento' => 'Bonifico'];
        $this->invia($this->p(self::HR, 'Hilda', 'hr@azienda.test'), [$this->p(self::BRUNO, 'Bruno', 'bruno@azienda.test'), $this->p(self::CARLA, 'Carla', 'carla@azienda.test')], $azienda, true);
        Mail::assertSent(RiepilogoReferenteMail::class, 1);

        $gruppo = IscrizioneCorso::first()->dati_fatturazione_corso_id;
        $nuovo = $this->p('PRCLGU70B15D612T', 'Piero', 'piero@azienda.test');
        $this->actingAs(Admin::first(), 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), [
            'nominativo' => $nuovo, 'fatturazione_modo' => 'esistente', 'fatturazione_esistente_id' => $gruppo, 'privacy_confermata' => 1,
        ])->assertRedirect();

        $this->assertStringNotContainsString('IBAN', $this->html($this->inviata(IscrizioneCorsoMail::class, 'piero@azienda.test')));
        Mail::assertSent(RiepilogoReferenteMail::class, 1);                              // nessun secondo riepilogo
    }

    public function test_il_template_del_riepilogo_si_vede_nel_pannello_con_anteprima(): void
    {
        $admin = Admin::first();

        $this->actingAs($admin, 'admin')->get(route('admin.email-templates.index'))->assertOk()->assertSee('Riepilogo per chi ha iscritto altre persone');
        $this->actingAs($admin, 'admin')->get(route('admin.email-templates.anteprima', 'riepilogo-referente'))
            ->assertOk()->assertSee('IT60 X054 2811 1010 0000 0123 456', false)->assertSee('Rossi Mario', false)->assertDontSee('{numero_iscritti}', false);

        $this->assertNotNull(EmailTemplate::perTipo('riepilogo-referente'));
    }
}
