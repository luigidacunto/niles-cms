<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnonymizePersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_sostituisce_i_dati_personali_e_mantiene_le_relazioni(): void
    {
        $m = Member::create([
            'codice_fiscale' => 'RSSMRA86L09H501Z', 'nome' => 'Mario', 'cognome' => 'Rossi', 'data_nascita' => '1986-07-09',
            'email' => 'mario@reale.it', 'telefono' => '3331234567', 'telefoni_aggiuntivi' => ['3339999999'],
            'ruolo' => Member::RUOLO_VOLONTARIO, 'disabilitato' => true,
        ]);
        $senzaEmail = Member::create(['codice_fiscale' => 'BNCLGU80A01H501X', 'nome' => 'Luigi', 'cognome' => 'Bianchi', 'ruolo' => Member::RUOLO_DIPENDENTE]);

        $fatt = DB::table('dati_fatturazione_corso')->insertGetId(['tipo' => 'privato', 'nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'mario@reale.it']);
        $persona = DB::table('persone')->insertGetId(['nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => 'RSSMRA86L09H501Z', 'email' => 'mario@reale.it']);
        // Il corso non serve al test: il controllo FK è rimandato al commit, che nel test non avviene (rollback).
        $corso = 1;
        DB::statement('PRAGMA defer_foreign_keys = ON');
        DB::table('iscrizioni_corso')->insert([
            'corso_id' => $corso, 'dati_fatturazione_corso_id' => $fatt, 'persona_id' => $persona, 'nome' => 'Mario', 'cognome' => 'Rossi',
            'email' => 'mario@reale.it', 'codice_fiscale' => 'RSSMRA86L09H501Z', 'privacy_accettata_at' => now(),
        ]);

        $this->artisan('dev:anonymize-personal-data', ['--force' => true])->assertSuccessful();

        $m->refresh();
        $this->assertNotSame('Mario', $m->nome);
        $this->assertMatchesRegularExpression('/^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$/', $m->codice_fiscale);
        $this->assertSame('socio'.$m->id.'@example.test', $m->email);
        $this->assertSame('1986', $m->data_nascita->format('Y'));
        $this->assertCount(1, $m->telefoni_aggiuntivi);
        $this->assertTrue($m->disabilitato);
        $this->assertNull($senzaEmail->fresh()->email);

        $p = DB::table('persone')->find($persona);
        $i = DB::table('iscrizioni_corso')->first();
        $this->assertSame($p->codice_fiscale, $i->codice_fiscale);
        $this->assertSame($p->email, $i->email);
        $this->assertSame($persona, $i->persona_id);
        $this->assertSame('fatturazione'.$fatt.'@example.test', DB::table('dati_fatturazione_corso')->find($fatt)->email);

        // Idempotente
        $cf = $m->codice_fiscale;
        $this->artisan('dev:anonymize-personal-data', ['--force' => true])->assertSuccessful();
        $this->assertSame($cf, $m->fresh()->codice_fiscale);
    }
}
