<?php

namespace Tests\Feature;

use App\Mail\IscrizioneCorsoMail;
use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\EmailTemplate;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\TipologiaCorso;
use App\Support\DatiPagamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Coordinate bancarie nell'email di iscrizione e nella pagina personale, solo per il bonifico di un corso a pagamento. */
class BonificoCorsiTest extends TestCase
{
    use RefreshDatabase;

    private Corso $corso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creaTemplateEmail();
        CommitteeInfo::current()->update([
            'denominazione' => 'Comitato Prova', 'iban' => 'IT60X0542811101000000123456',
            'intestatario_conto' => 'Comitato Prova APS', 'banca' => 'Banca Esempio',
        ]);
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $this->corso = Corso::create([
            'tipologia_corso_id' => TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD'])->id,
            'slug' => 'blsd-1', 'protocollo' => 'BLSD-1', 'admin_id' => $admin->id, 'costo' => 35, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    private function iscrizione(string $metodo, ?Corso $corso = null): IscrizioneCorso
    {
        $persona = Persona::daDati(['nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => 'RSSMRA80A01H501U', 'email' => 'mario@example.test']);

        return IscrizioneCorso::create([
            'corso_id' => ($corso ?? $this->corso)->id,
            'dati_fatturazione_corso_id' => DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => 'M', 'cognome' => 'R', 'metodo_pagamento' => $metodo])->id,
            'persona_id' => $persona->id, 'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'mario@example.test',
            'codice_fiscale' => 'RSSMRA80A01H501U', 'privacy_accettata_at' => now(),
        ])->load('corso.tipologia', 'datiFatturazione');
    }

    public function test_bonifico_con_quota_e_iban_mostra_le_coordinate(): void
    {
        $d = DatiPagamento::bonifico($this->iscrizione('Bonifico'));

        $this->assertSame('35,00 €', $d['importo']);
        $this->assertSame('Comitato Prova APS', $d['intestatario']);
        $this->assertSame('IT60 X054 2811 1010 0000 0123 456', $d['iban']);
        $this->assertSame('Corso BLSD-1 - Rossi Mario', $d['causale']);
    }

    public function test_intestatario_ricade_sulla_denominazione_del_comitato(): void
    {
        CommitteeInfo::current()->update(['intestatario_conto' => null]);

        $this->assertSame('Comitato Prova', DatiPagamento::bonifico($this->iscrizione('bonifico'))['intestatario']); // dati vecchi in minuscolo
    }

    public function test_niente_coordinate_per_contanti_corso_gratuito_o_senza_iban(): void
    {
        $this->assertNull(DatiPagamento::bonifico($this->iscrizione('Contanti')));
        $this->assertNull(DatiPagamento::bonifico($this->iscrizione('POS')->fresh('corso.tipologia', 'datiFatturazione')));
        IscrizioneCorso::query()->delete();

        $this->corso->update(['costo' => 0]);
        $this->assertNull(DatiPagamento::bonifico($this->iscrizione('Bonifico')->fresh('corso.tipologia', 'datiFatturazione')));

        $this->corso->update(['costo' => 35]);
        CommitteeInfo::current()->update(['iban' => null]);
        $this->assertNull(DatiPagamento::bonifico(IscrizioneCorso::first()->load('corso.tipologia', 'datiFatturazione')));
    }

    public function test_email_con_bonifico_contiene_le_coordinate_senza_bonifico_no(): void
    {
        $conBonifico = (new IscrizioneCorsoMail($this->iscrizione('Bonifico')))->render();
        $this->assertStringContainsString('IT60 X054 2811 1010 0000 0123 456', $conBonifico);
        $this->assertStringContainsString('Corso BLSD-1 - Rossi Mario', $conBonifico);
        $this->assertStringNotContainsString('{dati_pagamento}', $conBonifico);

        IscrizioneCorso::query()->delete();
        $contanti = (new IscrizioneCorsoMail($this->iscrizione('Contanti')))->render();
        $this->assertStringNotContainsString('IBAN', $contanti);
        $this->assertStringNotContainsString('{dati_pagamento}', $contanti);
    }

    public function test_pagina_personale_mostra_come_pagare_solo_per_corsi_in_programma(): void
    {
        $i = $this->iscrizione('Bonifico');
        $token = $i->persona->token;

        $this->get(route('preferenze.show', $token))->assertSee('Come pagare')->assertSee('IT60 X054 2811 1010 0000 0123 456');

        $this->corso->update(['data_inizio' => now()->subDays(3), 'data_fine' => now()->subDays(3)->addHours(4)]);
        $this->get(route('preferenze.show', $token))->assertDontSee('Come pagare');
    }

    public function test_template_un_valore_con_graffe_non_innesca_altri_segnaposto(): void
    {
        $r = EmailTemplate::render('iscrizione-corso', ['nome' => '{corso}', 'corso' => 'Corso vero'] + EmailTemplate::valoriEsempio('iscrizione-corso'));

        $this->assertStringContainsString('Ciao {corso},', $r['corpo']);   // restato testo, non sostituito
    }

    public function test_pannello_normalizza_e_valida_l_iban(): void
    {
        $admin = Admin::first();
        $base = ['denominazione' => 'Comitato Prova'];

        $this->actingAs($admin, 'admin')->put(route('admin.comitato.update'), $base + ['iban' => 'it60 x054 2811 1010 0000 0123 456'])->assertSessionDoesntHaveErrors();
        $this->assertSame('IT60X0542811101000000123456', CommitteeInfo::current()->iban);

        $this->actingAs($admin, 'admin')->put(route('admin.comitato.update'), $base + ['iban' => 'non-un-iban'])->assertSessionHasErrors('iban');

        // Vuoto = si può togliere.
        $this->actingAs($admin, 'admin')->put(route('admin.comitato.update'), $base + ['iban' => ''])->assertSessionDoesntHaveErrors();
        $this->assertNull(CommitteeInfo::current()->iban);
    }
}
