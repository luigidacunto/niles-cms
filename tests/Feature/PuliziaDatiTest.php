<?php

namespace Tests\Feature;

use App\Exports\IscrittiCorsoExport;
use App\Models\Admin;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\SicurezzaForm;
use App\Models\TipologiaCorso;
use App\Support\CsvSicuro;
use App\Support\Pulizia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Dati dei moduli puliti prima di salvarli (maiuscole/minuscole, spazi) e caratteri ammessi nei campi anagrafici. */
class PuliziaDatiTest extends TestCase
{
    use RefreshDatabase;

    private Corso $corso;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        SicurezzaForm::current()->update(['rate_limiting_attivo' => false]);   // il test fa molti invii di fila
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $this->corso = Corso::create([
            'tipologia_corso_id' => TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD'])->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1',
            'admin_id' => $admin->id, 'costo' => 0, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    private function sporchi(array $extra = []): array
    {
        return array_merge([
            'nome' => '  mARIO ', 'cognome' => "d'ANGELO  rossi", 'email' => ' Mario.Rossi@Example.TEST ', 'telefono' => ' +39  333 111 2222 ',
            'codice_fiscale' => ' rssmra80a01h501u ', 'via' => 'via roma 12/a', 'comune' => 'MONTE san savino', 'provincia' => 'ar', 'cap' => '52048',
        ], $extra);
    }

    private function invia(array $nominativo)
    {
        return $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => array_intersect_key($nominativo, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => [$nominativo], 'fatturazione_unica' => 1, 'fatturazione_come_iscritto' => 1,
            'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_accettata' => 1,
        ]);
    }

    public function test_pulizia_uniforma_maiuscole_e_spazi(): void
    {
        $this->assertSame("D'Angelo Rossi", Pulizia::titolo("  d'ANGELO   rossi "));
        $this->assertSame('Monte San Savino', Pulizia::titolo('MONTE san savino'));
        $this->assertSame('Via Roma 12/A', Pulizia::titolo('via roma 12/a'));
        $this->assertSame('Jean-Luc Élodie', Pulizia::titolo('jean-luc élodie'));
        $this->assertSame('RSSMRA80A01H501U', Pulizia::maiuscolo(' rssmra 80a01h501u '));
        $this->assertSame('a@b.it', Pulizia::minuscolo(' A@B.It '));
        $this->assertSame(['x' => 'Lasciato Com\'è', 'nome' => 'Mario'], Pulizia::persona(['x' => 'Lasciato Com\'è', 'nome' => 'MARIO']));   // chiavi sconosciute intatte
        $this->assertNull(Pulizia::titolo(null));
        $this->assertSame('', Pulizia::titolo('   '));
    }

    public function test_il_modulo_pubblico_salva_i_dati_puliti(): void
    {
        $this->invia($this->sporchi())->assertRedirect()->assertSessionHasNoErrors();

        $i = IscrizioneCorso::first();
        $this->assertSame(['Mario', "D'Angelo Rossi", 'mario.rossi@example.test', '+39 333 111 2222'], [$i->nome, $i->cognome, $i->email, $i->telefono]);
        $this->assertSame(['RSSMRA80A01H501U', 'Via Roma 12/A', 'Monte San Savino', 'AR', '52048'], [$i->codice_fiscale, $i->via, $i->comune, $i->provincia, $i->cap]);

        $p = Persona::first();
        $this->assertSame(['Mario', "D'Angelo Rossi", 'RSSMRA80A01H501U', 'mario.rossi@example.test'], [$p->nome, $p->cognome, $p->codice_fiscale, $p->email]);

        $f = $i->datiFatturazione;
        $this->assertSame(['Mario', "D'Angelo Rossi", 'RSSMRA80A01H501U', 'AR'], [$f->nome, $f->cognome, $f->codice_fiscale, $f->provincia]);
    }

    public function test_fatturazione_azienda_pulita_ma_la_ragione_sociale_resta_com_e(): void
    {
        $n = $this->sporchi();
        $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => array_intersect_key($n, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => [$n], 'fatturazione_unica' => 1, 'privacy_accettata' => 1,
            'fatturazione' => ['tipo' => 'azienda', 'ragione_sociale' => '  ACME   s.r.l.  ', 'partita_iva' => '12345678901', 'codice_destinatario' => ' abc1234 ',
                'pec' => ' AMM@PEC.Acme.test ', 'via' => 'largo garibaldi 3', 'comune' => 'bologna', 'provincia' => 'bo', 'cap' => '40100', 'metodo_pagamento' => 'Bonifico'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $f = DatiFatturazioneCorso::first();
        $this->assertSame('ACME s.r.l.', $f->ragione_sociale);                 // sigle e marchi non si toccano (solo gli spazi)
        $this->assertSame(['ABC1234', 'amm@pec.acme.test', 'Largo Garibaldi 3', 'Bologna', 'BO'], [$f->codice_destinatario, $f->pec, $f->via, $f->comune, $f->provincia]);
    }

    public function test_caratteri_non_ammessi_respinti(): void
    {
        $casi = [
            'nominativi.0.nome' => ['nome' => '<script>alert(1)</script>'],
            'nominativi.0.cognome' => ['cognome' => 'Rossi; DROP TABLE'],
            'nominativi.0.comune' => ['comune' => 'Bologna2'],
            'nominativi.0.via' => ['via' => 'Via "Roma" {1}'],
            'nominativi.0.telefono' => ['telefono' => '333-abc'],
            'nominativi.0.provincia' => ['provincia' => 'ARR'],
            'nominativi.0.cap' => ['cap' => '5210'],
        ];

        foreach ($casi as $campo => $dato) {
            $this->invia($this->sporchi($dato))->assertSessionHasErrors($campo);
        }

        $this->assertSame(0, IscrizioneCorso::count());
        $this->assertSame(0, Persona::count());
    }

    public function test_messaggi_di_errore_leggibili(): void
    {
        $r = $this->invia($this->sporchi(['nome' => 'Mario<b>']));

        $r->assertSessionHasErrors(['nominativi.0.nome' => 'Il campo nome della persona da iscrivere può contenere solo lettere, spazi, apostrofi, punti e trattini.']);
    }

    public function test_nomi_e_indirizzi_legittimi_passano(): void
    {
        $this->invia($this->sporchi(['nome' => 'Jean-Luc', 'cognome' => "Dell'Orto", 'comune' => "Sant'Angelo in Vado", 'via' => "Via dell'Olmo 12, int. 3/B", 'telefono' => '(0575) 24-398']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame("Via Dell'Olmo 12, Int. 3/B", IscrizioneCorso::first()->via);
    }

    public function test_inserimento_a_mano_dall_admin_pulisce_e_valida_allo_stesso_modo(): void
    {
        $admin = Admin::first();
        $payload = ['nominativo' => $this->sporchi(), 'fatturazione_modo' => 'stessi', 'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_confermata' => 1];

        $this->actingAs($admin, 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['Mario', 'RSSMRA80A01H501U', 'AR', 'mario.rossi@example.test'],
            [IscrizioneCorso::first()->nome, IscrizioneCorso::first()->codice_fiscale, IscrizioneCorso::first()->provincia, IscrizioneCorso::first()->email]);

        $payload['nominativo'] = $this->sporchi(['codice_fiscale' => 'VRDNNA85M41F205Z', 'nome' => 'Anna<b>']);
        $this->actingAs($admin, 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), $payload)->assertSessionHasErrors('nominativo.nome');
    }

    public function test_celle_csv_neutralizzate_nell_export_degli_iscritti(): void
    {
        $this->assertSame("'=1+1", CsvSicuro::cella('=1+1'));
        $this->assertSame("'@SUM(A1)", CsvSicuro::cella('@SUM(A1)'));
        $this->assertSame('Mario', CsvSicuro::cella('Mario'));
        $this->assertSame('', CsvSicuro::cella(null));

        $persona = Persona::daDati(['nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => 'RSSMRA80A01H501U', 'email' => '=cmd@example.test']);
        $i = IscrizioneCorso::create([
            'corso_id' => $this->corso->id,
            'dati_fatturazione_corso_id' => DatiFatturazioneCorso::create(['tipo' => 'azienda', 'ragione_sociale' => '=HYPERLINK("http://x")', 'metodo_pagamento' => 'Bonifico'])->id,
            'persona_id' => $persona->id, 'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => '=cmd@example.test', 'telefono' => '+39 333 1112222',
            'codice_fiscale' => 'RSSMRA80A01H501U', 'privacy_accettata_at' => now(),
        ]);

        // CSV
        $csv = $this->actingAs(Admin::first(), 'admin')->get(route('admin.corsi.iscritti.export', [$this->corso, 'csv']))->streamedContent();
        $this->assertStringContainsString("'=cmd@example.test", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('+39 333 1112222', $csv);               // il telefono (solo cifre e + ( ) . -) non ha l'apostrofo

        // Excel (stessa neutralizzazione sulla collezione)
        $riga = (new IscrittiCorsoExport($this->corso))->collection()->first();
        $this->assertSame("'=cmd@example.test", $riga['Email']);
        $this->assertSame("'=HYPERLINK(\"http://x\")", $riga['Fatturazione a']);
    }

    public function test_etichetta_indirizzo_generica(): void
    {
        $this->get('/corsi/blsd-1/iscrizione')->assertOk()->assertSee('Via / largo / piazza / località');
    }
}

