<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\IscrizioneCorso;
use App\Models\Member;
use App\Models\SicurezzaForm;
use App\Models\TipologiaCorso;
use App\Services\MemberImportService;
use App\Support\CodiceFiscaleInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/** Verifica del codice fiscale (lettere di nome/cognome e data di nascita): le ultime 5 posizioni non si controllano. */
class CodiceFiscaleCoerenzaTest extends TestCase
{
    use RefreshDatabase;

    private const GIUSTO = 'CPCGLI88R55A390J';   // Giulia Capacci, 15/10/1988

    private const ERRATO = 'COCGLI88R55A390J';   // «O» al posto della «P»

    public function test_casi_di_incoerenza(): void
    {
        $this->assertSame([], CodiceFiscaleInfo::incoerenze(self::GIUSTO, 'Giulia', 'Capacci'));
        $this->assertSame([], CodiceFiscaleInfo::incoerenze(self::GIUSTO));                                   // senza nomi: solo struttura e data
        $this->assertSame([], CodiceFiscaleInfo::incoerenze('RSSMRA8LALMH501U', 'Mario', 'Rossi'));            // omocodia
        $this->assertSame([], CodiceFiscaleInfo::incoerenze('DLLJLC80A01H501X', 'Jean-Luc', "Dell'Orto"));     // 4 consonanti nel nome, apostrofo
        $this->assertSame([], CodiceFiscaleInfo::incoerenze('NCLCRL80A01H501X', 'Carlo', 'Nicolò'));          // accento
        $this->assertSame([], CodiceFiscaleInfo::incoerenze('FOXLCU80A01H501X', 'Luca', 'Fo'));                // cognome corto: X di riempimento
        $this->assertSame([], CodiceFiscaleInfo::incoerenze(self::ERRATO));                                   // senza nomi la sola «O» non è rilevabile

        $cognome = CodiceFiscaleInfo::incoerenze(self::ERRATO, 'Giulia', 'Capacci');
        $this->assertCount(1, $cognome);
        $this->assertStringContainsString('cognome «Capacci»', $cognome[0]);

        $this->assertStringContainsString('nome «Giuseppe»', CodiceFiscaleInfo::incoerenze(self::GIUSTO, 'Giuseppe', 'Capacci')[0]);
        $this->assertStringContainsString('invertiti', CodiceFiscaleInfo::incoerenze('RSSMRA80A01H501U', 'Rossi', 'Mario')[0]);

        $this->assertStringContainsString('mese', CodiceFiscaleInfo::incoerenze('CPCGLI88Z55A390J')[0]);
        $this->assertStringContainsString('giorno', CodiceFiscaleInfo::incoerenze('CPCGLI88R32A390J')[0]);   // giorno 32
        $this->assertStringContainsString('giorno', CodiceFiscaleInfo::incoerenze('CPCGLI88D31A390J')[0]);   // 31 aprile
        $this->assertStringContainsString('anno', CodiceFiscaleInfo::incoerenze('CPCGLIAAR55A390J')[0]);
        $this->assertStringContainsString('lettere', CodiceFiscaleInfo::incoerenze('CP1GLI88R55A390J')[0]);

        // Le ultime 5 posizioni (comune + carattere di controllo) non si verificano: nessun avviso se sono diverse.
        $this->assertSame([], CodiceFiscaleInfo::incoerenze('CPCGLI88R55Z999Q', 'Giulia', 'Capacci'));
    }

    private function corso(): Corso
    {
        SicurezzaForm::current()->update(['rate_limiting_attivo' => false]);
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);

        return Corso::create([
            'tipologia_corso_id' => TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD'])->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1',
            'admin_id' => $admin->id, 'costo' => 0, 'pubblicato' => true,
            'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    private function persona(string $cf): array
    {
        return ['nome' => 'Giulia', 'cognome' => 'Capacci', 'email' => 'g@example.test', 'telefono' => '333', 'codice_fiscale' => $cf,
            'via' => 'Via Roma 1', 'comune' => 'Arezzo', 'provincia' => 'AR', 'cap' => '52100'];
    }

    public function test_il_modulo_pubblico_respinge_il_cf_che_non_torna_dicendo_cosa(): void
    {
        Mail::fake();
        $this->corso();

        $invia = fn (array $richiedente, array $nominativo) => $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => $richiedente, 'nominativi' => [$nominativo], 'fatturazione_unica' => 1, 'fatturazione_come_iscritto' => 1,
            'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_accettata' => 1, 'autodichiarazione_terzi' => 1,
        ]);

        // Il caso reale: referente con il CF giusto, partecipante (se stessa) con la «O» al posto della «P».
        $invia($this->persona(self::GIUSTO), $this->persona(self::ERRATO))->assertSessionHasErrors('nominativi.0.codice_fiscale');
        $this->assertSame(0, IscrizioneCorso::count());

        // Referente con il CF sbagliato: l'errore è sul suo campo.
        $invia($this->persona(self::ERRATO), $this->persona(self::GIUSTO))->assertSessionHasErrors('richiedente.codice_fiscale');

        $invia($this->persona(self::GIUSTO), $this->persona(self::GIUSTO))->assertSessionHasNoErrors();
        $this->assertSame(1, IscrizioneCorso::count());
    }

    public function test_la_segreteria_controlla_la_data_ma_non_il_nome(): void
    {
        Mail::fake();
        $corso = $this->corso();
        $admin = Admin::first();
        $manda = fn (string $cf) => $this->actingAs($admin, 'admin')->post(route('admin.corsi.iscritti.store', $corso), [
            'nominativo' => $this->persona($cf), 'fatturazione_modo' => 'stessi', 'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_confermata' => 1,
        ]);

        $manda('CPCGLI88Z55A390J')->assertSessionHasErrors('nominativo.codice_fiscale');   // mese impossibile: sempre respinto
        $manda(self::ERRATO)->assertSessionHasNoErrors();                                   // nome diverso: la segreteria ha il documento davanti
    }

    public function test_anagrafica_membri_non_cambia_e_l_anteprima_import_avvisa_senza_bloccare(): void
    {
        // Il form dei membri usa solo CodiceFiscale (formato): un CF «strano» ma di 16 caratteri passa come prima.
        $this->assertTrue(Member::create(['codice_fiscale' => 'AAAAAA00A00A000A', 'nome' => 'X', 'cognome' => 'Y', 'ruolo' => Member::RUOLO_DIPENDENTE])->exists);

        $sheet = ($wb = new Spreadsheet)->getActiveSheet();
        $sheet->fromArray(['Cognome', 'Nome', 'Codice Fiscale', 'Data di Nascita', 'Email', 'Numeri di telefono', 'Tipo Attuale'], null, 'A1');
        $sheet->fromArray(['Capacci', 'Giulia', self::ERRATO, '15/10/1988', '', '', 'Dipendente'], null, 'A2');
        $sheet->fromArray(['Capacci', 'Giulia', self::GIUSTO, '15/10/1988', '', '', 'Dipendente'], null, 'A3');
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($wb))->save($path);

        $svc = new MemberImportService;
        $plan = $svc->plan($path, 'dipendenti');
        $ops = collect($plan['ops'])->where('op', MemberImportService::OP_NUOVO)->keyBy('cf');

        $this->assertStringContainsString('Codice fiscale da controllare', $ops[self::ERRATO]['avviso']);
        $this->assertStringContainsString('«Capacci»', $ops[self::ERRATO]['avviso']);
        $this->assertNull($ops[self::GIUSTO]['avviso']);

        $svc->apply($plan);                                                                  // l'avviso non impedisce l'import
        $this->assertNotNull(Member::firstWhere('codice_fiscale', self::ERRATO));
    }
}
