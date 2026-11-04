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

/** Strumenti correttivi riservati agli admin: correzione iscritto/fatturazione, sostituzione referente, eliminazione persona. */
class CorrezioniAdminTest extends TestCase
{
    use RefreshDatabase;

    private const CF_GIUSTO = 'RSSMRA80A01H501U';

    private const CF_ERRATO = 'RSSMRA80A01H501X';

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

    private function iscrivi(string $cf, string $nome = 'Mario', string $cognome = 'Rossi', ?Persona $referente = null, ?DatiFatturazioneCorso $fatt = null): IscrizioneCorso
    {
        $persona = Persona::daDati(['nome' => $nome, 'cognome' => $cognome, 'codice_fiscale' => $cf, 'email' => strtolower($nome).'@example.test']);

        return IscrizioneCorso::create([
            'corso_id' => $this->corso->id,
            'dati_fatturazione_corso_id' => ($fatt ?? DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => $nome, 'cognome' => $cognome, 'codice_fiscale' => $cf, 'metodo_pagamento' => 'Bonifico', 'email' => 'ref@example.test']))->id,
            'persona_id' => $persona->id, 'referente_persona_id' => $referente?->id, 'nome' => $nome, 'cognome' => $cognome, 'email' => $persona->email,
            'telefono' => '333', 'codice_fiscale' => $cf, 'privacy_accettata_at' => now(),
        ]);
    }

    private function payload(IscrizioneCorso $i, array $nominativo = [], array $fatturazione = []): array
    {
        return [
            'nominativo' => $nominativo + [
                'nome' => $i->nome, 'cognome' => $i->cognome, 'email' => $i->email, 'telefono' => '333', 'codice_fiscale' => $i->codice_fiscale,
                'via' => 'Via Roma 1', 'comune' => 'Arezzo', 'provincia' => 'AR', 'cap' => '52100',
            ],
            'fatturazione' => $fatturazione + ['tipo' => 'privato', 'nome' => $i->nome, 'cognome' => $i->cognome, 'metodo_pagamento' => 'Bonifico'],
        ];
    }

    public function test_correggere_il_cf_riaggancia_alla_persona_giusta_e_la_errata_si_elimina(): void
    {
        $giusta = Persona::daDati(['nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => self::CF_GIUSTO, 'email' => 'mario@example.test']);
        $iscrizione = $this->iscrivi(self::CF_ERRATO);
        $errata = $iscrizione->persona;
        $errata->registraConsenso('promemoria', 'confermato', 'link_personale');

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.corsi.iscritti.aggiorna', [$this->corso, $iscrizione]), $this->payload($iscrizione, ['codice_fiscale' => self::CF_GIUSTO]))
            ->assertRedirect(route('admin.corsi.iscritti.index', $this->corso))
            ->assertSessionHas('status');

        $iscrizione->refresh();
        $this->assertSame($giusta->id, $iscrizione->persona_id);
        $this->assertSame(self::CF_GIUSTO, $iscrizione->codice_fiscale);
        $this->assertSame('confermato', $giusta->fresh()->promemoria_stato);       // il consenso non si perde
        $this->assertSame(1, $giusta->consensi()->count());
        $this->assertTrue($errata->fresh()->eliminabile());

        $this->actingAs($this->admin, 'admin')->delete(route('admin.persone.destroy', $errata))->assertRedirect(route('admin.persone.index'));
        $this->assertNull(Persona::find($errata->id));
        $this->assertSame(1, IscrizioneCorso::count());
    }

    public function test_referente_che_coincide_col_partecipante_torna_vuoto(): void
    {
        $giusta = Persona::daDati(['nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => self::CF_GIUSTO, 'email' => 'mario@example.test']);
        $iscrizione = $this->iscrivi(self::CF_ERRATO, referente: $giusta);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.corsi.iscritti.aggiorna', [$this->corso, $iscrizione]), $this->payload($iscrizione, ['codice_fiscale' => self::CF_GIUSTO]))
            ->assertSessionHasNoErrors();

        $this->assertNull($iscrizione->fresh()->referente_persona_id);
    }

    public function test_cf_gia_iscritto_al_corso_e_rifiutato(): void
    {
        $this->iscrivi(self::CF_GIUSTO);
        $altra = $this->iscrivi(self::CF_ERRATO, 'Anna', 'Verdi');

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.corsi.iscritti.aggiorna', [$this->corso, $altra]), $this->payload($altra, ['codice_fiscale' => self::CF_GIUSTO]))
            ->assertSessionHasErrors('nominativo.codice_fiscale');

        $this->assertSame(self::CF_ERRATO, $altra->fresh()->codice_fiscale);
    }

    public function test_fatturazione_condivisa_si_corregge_per_tutti_senza_toccare_i_contatti(): void
    {
        $a = $this->iscrivi('RSSMRA80A01H501U');
        $b = $this->iscrivi('VRDNNA85M41F205Z', 'Anna', 'Verdi', fatt: $a->datiFatturazione);

        $this->actingAs($this->admin, 'admin')->get(route('admin.corsi.iscritti.correggi', [$this->corso, $a]))->assertOk()->assertSee('condivisi con 1 altro iscritto');
        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.corsi.iscritti.aggiorna', [$this->corso, $a]), $this->payload($a, [], ['tipo' => 'azienda', 'ragione_sociale' => 'Acme Srl', 'partita_iva' => '12345678901', 'metodo_pagamento' => 'Contanti']))
            ->assertSessionHasNoErrors();

        $f = $b->fresh()->datiFatturazione;
        $this->assertSame('azienda', $f->tipo);
        $this->assertSame('Acme Srl', $f->ragione_sociale);
        $this->assertNull($f->nome);
        $this->assertSame('Contanti', $f->metodo_pagamento);
        $this->assertSame('ref@example.test', $f->email);                           // contatto di chi ha compilato: intatto
    }

    public function test_persona_con_iscrizioni_o_referente_non_si_elimina_e_il_referente_si_sostituisce(): void
    {
        $sbagliato = Persona::daDati(['nome' => 'Giulia', 'cognome' => 'Capacci', 'codice_fiscale' => 'CPCGLI88R55A390J', 'email' => 'g@example.test']);
        $giusto = Persona::daDati(['nome' => 'Giulia', 'cognome' => 'Capacci', 'codice_fiscale' => 'RSSMRA80A01H501U', 'email' => 'g@example.test']);
        $iscrizione = $this->iscrivi('VRDNNA85M41F205Z', 'Anna', 'Verdi', referente: $sbagliato);
        $a = $this->actingAs($this->admin, 'admin');

        $a->delete(route('admin.persone.destroy', $sbagliato))->assertSessionHasErrors('elimina');
        $this->assertNotNull(Persona::find($sbagliato->id));                        // niente referente svuotato in silenzio
        $this->assertSame($sbagliato->id, $iscrizione->fresh()->referente_persona_id);

        $a->post(route('admin.persone.sostituisci-referente', $sbagliato), ['codice_fiscale' => 'RSSMRA80A01H501U'])->assertSessionHas('status');
        $this->assertSame($giusto->id, $iscrizione->fresh()->referente_persona_id);

        $a->delete(route('admin.persone.destroy', $sbagliato))->assertRedirect();
        $this->assertNull(Persona::find($sbagliato->id));
    }

    public function test_solo_gli_admin_possono(): void
    {
        $editor = Admin::create(['name' => 'E', 'email' => 'e@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true, 'permissions' => ['corsi' => ['read', 'write']]]);
        $iscrizione = $this->iscrivi(self::CF_GIUSTO);
        $e = $this->actingAs($editor, 'admin');

        $e->get(route('admin.corsi.iscritti.correggi', [$this->corso, $iscrizione]))->assertForbidden();
        $e->put(route('admin.corsi.iscritti.aggiorna', [$this->corso, $iscrizione]), $this->payload($iscrizione))->assertForbidden();
        $e->delete(route('admin.persone.destroy', $iscrizione->persona))->assertForbidden();
        $e->post(route('admin.persone.sostituisci-referente', $iscrizione->persona), ['codice_fiscale' => self::CF_ERRATO])->assertForbidden();
    }
}
