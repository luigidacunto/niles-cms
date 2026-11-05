<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\IscrizioneCorso;
use App\Models\PrivacyPolicy;
use App\Models\Stato;
use App\Models\TipologiaCorso;
use App\Support\CodiceFiscaleInfo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Tipologie con «anagrafica completa»: data e luogo di nascita richiesti e controllo CF rafforzato (per l'attestato). */
class AnagraficaCompletaTest extends TestCase
{
    use RefreshDatabase;

    /** Mario Rossi, nato il 10/12/1985 (T = dicembre) a Roma (H501), carattere di controllo calcolato. */
    private const CF_ITALIA = 'RSSMRA85T10H501';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->creaTemplateEmail();
        $this->withoutVite();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);   // più invii di seguito nello stesso test
        Stato::popolaPredefiniti();
        $this->admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
    }

    private function cf(string $quindici = self::CF_ITALIA): string
    {
        return $quindici.CodiceFiscaleInfo::carattereControllo($quindici);
    }

    private function corso(bool $anagrafica): Corso
    {
        $t = TipologiaCorso::create(['nome' => 'BLSD', 'sigla' => 'BLSD', 'anagrafica_completa' => $anagrafica]);

        return Corso::create([
            'tipologia_corso_id' => $t->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1', 'admin_id' => $this->admin->id,
            'costo' => 0, 'pubblicato' => true, 'data_inizio' => now()->addDays(5), 'data_fine' => now()->addDays(5)->addHours(4),
        ]);
    }

    private function invio(string $cf, array $nascita): \Illuminate\Testing\TestResponse
    {
        $p = ['nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'm@example.test', 'telefono' => '333', 'codice_fiscale' => $cf,
            'via' => 'Via Roma 1', 'comune' => 'Roma', 'provincia' => 'RM', 'cap' => '00100'];

        return $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => array_intersect_key($p, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => [$p + $nascita],
            'privacy_accettata' => 1,
        ]);
    }

    public function test_carattere_di_controllo_noto(): void
    {
        $this->assertSame('S', CodiceFiscaleInfo::carattereControllo('RSSMRA85T10A562'));   // Mario Rossi, esempio classico
    }

    public function test_il_modulo_chiede_data_e_luogo_di_nascita_solo_per_le_tipologie_che_lo_richiedono(): void
    {
        $this->corso(true);
        $this->get('/corsi/blsd-1/iscrizione')->assertOk()->assertSee('Data di nascita')->assertSee("necessari per il rilascio dell'attestato", false);

        TipologiaCorso::query()->update(['anagrafica_completa' => false]);
        $this->get('/corsi/blsd-1/iscrizione')->assertOk()->assertDontSee('Data di nascita');
    }

    public function test_iscrizione_con_dati_di_nascita_coerenti_viene_salvata(): void
    {
        $corso = $this->corso(true);

        $this->invio($this->cf(), ['data_nascita' => '1985-12-10', 'nascita_luogo' => 'italia', 'nascita_comune' => 'Roma', 'nascita_provincia' => 'RM'])
            ->assertSessionDoesntHaveErrors()->assertRedirect();

        $i = IscrizioneCorso::where('corso_id', $corso->id)->sole();
        $this->assertSame('1985-12-10', $i->data_nascita->toDateString());
        $this->assertFalse($i->nascita_estero);
        $this->assertSame('Roma (RM)', $i->luogoNascita());
        $this->assertNull($i->nascita_stato);
    }

    public function test_filtro_dati_mancanti_sugli_iscritti(): void
    {
        $corso = $this->corso(true);
        $base = ['corso_id' => $corso->id, 'telefono' => '3', 'via' => 'v', 'comune' => 'c', 'provincia' => 'AR', 'cap' => '52100', 'email' => 'x@example.test', 'privacy_accettata_at' => now()];
        $persona = fn ($n) => \App\Models\Persona::create(['nome' => $n, 'cognome' => 'Test', 'codice_fiscale' => 'FKPANO00X00X00'.$n.'X', 'email' => 'x@example.test', 'cellulare' => '3']);
        foreach (['1' => 'Vecchio', '2' => 'Completo'] as $n => $nome) {
            $extra = (string) $n === '2' ? ['data_nascita' => '1990-01-01', 'nascita_estero' => false, 'nascita_comune' => 'Roma', 'nascita_provincia' => 'RM'] : [];
            IscrizioneCorso::create($base + $extra + ['persona_id' => $persona($n)->id, 'nome' => $nome, 'cognome' => 'Test', 'codice_fiscale' => 'FKPANO00X00X00'.$n.'X']);
        }

        $r = $this->actingAs($this->admin, 'admin')->get(route('admin.corsi.iscritti.index', [$corso, 'stato' => 'mancanti']));
        $r->assertOk()->assertSee('Vecchio')->assertDontSee('Completo')->assertSee('Dati mancanti (1)');
    }

    public function test_controlli_rafforzati_sul_codice_fiscale(): void
    {
        $this->corso(true);
        $nascita = ['data_nascita' => '1985-12-10', 'nascita_luogo' => 'italia', 'nascita_comune' => 'Roma', 'nascita_provincia' => 'RM'];
        $albania = (string) Stato::where('codice_iso', 'AL')->value('id');

        $casi = [
            'carattere di controllo sbagliato' => [self::CF_ITALIA.'A', $nascita, 'nominativi.0.codice_fiscale'],
            'data diversa dal codice' => [$this->cf(), ['data_nascita' => '1985-12-11'] + $nascita, 'nominativi.0.codice_fiscale'],
            'codice Italia ma scelto uno Stato estero' => [$this->cf(), ['nascita_luogo' => $albania] + $nascita, 'nominativi.0.codice_fiscale'],
            'dati di nascita mancanti (data)' => [$this->cf(), [], 'nominativi.0.data_nascita'],
            'dati di nascita mancanti (luogo)' => [$this->cf(), [], 'nominativi.0.nascita_luogo'],
            'senza comune' => [$this->cf(), ['nascita_comune' => ''] + $nascita, 'nominativi.0.nascita_comune'],
            'Italia senza provincia' => [$this->cf(), ['nascita_provincia' => ''] + $nascita, 'nominativi.0.nascita_provincia'],
            'luogo inventato' => [$this->cf(), ['nascita_luogo' => '99999'] + $nascita, 'nominativi.0.nascita_luogo'],
        ];

        foreach ($casi as $nome => [$cf, $dati, $campo]) {
            try {
                $this->invio($cf, $dati)->assertSessionHasErrors($campo);
            } catch (\PHPUnit\Framework\AssertionFailedError) {
                $this->fail("Atteso errore su {$campo}: {$nome}");
            }
        }
        $this->assertSame(0, IscrizioneCorso::count());

        // Il messaggio nomina lo Stato scelto, non un generico «Stato estero».
        $this->invio($this->cf(), ['nascita_luogo' => $albania] + $nascita)->assertSessionHasErrors([
            'nominativi.0.codice_fiscale' => 'Il codice fiscale è di una persona nata in Italia, ma come luogo di nascita è indicato «Albania»: correggi il luogo di nascita o il codice fiscale.',
        ]);
    }

    public function test_dopo_un_errore_il_modulo_riparte_dai_dati_inviati(): void
    {
        $this->corso(true);
        $this->from('/corsi/blsd-1/iscrizione');
        $this->invio($this->cf(), ['data_nascita' => '1985-12-11', 'nascita_luogo' => 'italia', 'nascita_comune' => 'Fiesole', 'nascita_provincia' => 'FI'])
            ->assertSessionHasErrors('nominativi.0.codice_fiscale');

        // Lo stato Alpine del modulo parte dai valori inviati, compresi residenza e nascita (prima ripartiva vuoto).
        $this->get('/corsi/blsd-1/iscrizione')->assertOk()
            ->assertSee('Via Roma 1')->assertSee('1985-12-11')->assertSee('Fiesole')->assertSee('m@example.test');
    }

    public function test_nato_all_estero_stato_da_elenco_provincia_ee_forzata(): void
    {
        $corso = $this->corso(true);
        $albania = Stato::where('codice_iso', 'AL')->sole();

        // Anche se il modulo inviasse una provincia diversa, il server mette EE.
        $this->invio($this->cf('RSSMRA85T10Z100'), ['data_nascita' => '1985-12-10', 'nascita_luogo' => (string) $albania->id, 'nascita_comune' => 'Tirana', 'nascita_provincia' => 'RM'])
            ->assertSessionDoesntHaveErrors()->assertRedirect();

        $i = IscrizioneCorso::where('corso_id', $corso->id)->sole();
        $this->assertTrue($i->nascita_estero);
        $this->assertSame('EE', $i->nascita_provincia);
        $this->assertSame('Albania', $i->nascita_stato);
        $this->assertSame('Tirana (EE), Albania', $i->luogoNascita());
    }

    public function test_stato_non_in_elenco_con_testo_libero(): void
    {
        $corso = $this->corso(true);
        $base = ['data_nascita' => '1985-12-10', 'nascita_luogo' => 'altro', 'nascita_comune' => 'Lipsia'];

        $this->invio($this->cf('RSSMRA85T10Z111'), $base)->assertSessionHasErrors('nominativi.0.nascita_stato_altro');
        $this->invio($this->cf('RSSMRA85T10Z111'), $base + ['nascita_stato_altro' => 'germania est'])->assertSessionDoesntHaveErrors();

        $this->assertSame('Germania Est', IscrizioneCorso::where('corso_id', $corso->id)->sole()->nascita_stato);
    }

    public function test_gli_stati_sono_una_tabella_aggiornabile(): void
    {
        $this->assertSame(0, Stato::where('nome', 'Italia')->count());
        $totale = Stato::count();
        $this->assertGreaterThan(190, $totale);

        Stato::where('codice_iso', 'AL')->update(['attivo' => false, 'nome' => 'Albania (modificata)']);
        Stato::popolaPredefiniti();   // create-only: non riattiva né rinomina
        $this->assertSame($totale, Stato::count());
        $this->assertFalse(Stato::where('codice_iso', 'AL')->sole()->attivo);
        $this->assertFalse(Stato::elencoAttivi()->contains('codice_iso', 'AL'));
    }

    public function test_le_tipologie_senza_il_flag_non_cambiano(): void
    {
        $corso = $this->corso(false);
        $p = ['nome' => 'Mario', 'cognome' => 'Rossi', 'email' => 'm@example.test', 'telefono' => '333', 'codice_fiscale' => 'RSSMRA85T10H501B',
            'via' => 'Via Roma 1', 'comune' => 'Roma', 'provincia' => 'RM', 'cap' => '00100'];

        // Carattere di controllo non verificato (scelta storica) e nessun dato di nascita richiesto.
        $this->post('/corsi/blsd-1/iscrizione', [
            'richiedente' => array_intersect_key($p, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => [$p], 'privacy_accettata' => 1,
        ])->assertSessionDoesntHaveErrors();

        $this->assertNull(IscrizioneCorso::where('corso_id', $corso->id)->sole()->data_nascita);
    }

    public function test_l_aggiornamento_dell_informativa_tocca_solo_il_testo_predefinito(): void
    {
        $vecchio = '<li>Dati anagrafici (nome, cognome, codice fiscale, residenza, email, telefono).</li>';
        $default = PrivacyPolicy::create(['tipo' => 'corsi-popolazione', 'is_default' => true, 'titolo' => 'T', 'testo' => $vecchio]);
        $personalizzata = PrivacyPolicy::create(['tipo' => 'corsi-popolazione', 'is_default' => false, 'titolo' => 'T', 'testo' => $vecchio]);

        (require database_path('seeders/aggiornamenti/2026_11_05_130000_informativa_corsi_dati_di_nascita.php'))->run();

        $this->assertStringContainsString('data e luogo di nascita', $default->fresh()->testo);
        $this->assertSame($vecchio, $personalizzata->fresh()->testo);
    }
}
