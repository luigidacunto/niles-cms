<?php

namespace Tests\Feature;

use App\Mail\IscrizioneCorsoMail;
use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\EmailTemplate;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\TipologiaCorso;
use App\Support\IcsEvento;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Email di iscrizione (template + promemoria .ics), richiesta dei consensi, pagina personale /preferenze/{token}
 * e pannello dei template.
 */
class EmailIscrizionePreferenzeTest extends TestCase
{
    use RefreshDatabase;

    private const ADULTO_A = 'RSSMRA80A01H501U';

    private const ADULTO_B = 'NRIPLA70B15D612T';

    private const ADULTA_C = 'VRDLGI85M41F205Z';

    private Corso $corso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creaTemplateEmail();
        CommitteeInfo::current()->update(['email' => 'segreteria@comitato.test', 'telefono' => '0575 000000', 'indirizzo' => 'Via Roma 1, Bologna']);

        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $tipologia = TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD']);
        $this->corso = Corso::create([
            'tipologia_corso_id' => $tipologia->id, 'slug' => 'blsd-1', 'protocollo' => 'BLSD-1', 'admin_id' => $admin->id,
            'data_inizio' => Carbon::create(2026, 10, 10, 9, 0), 'data_fine' => Carbon::create(2026, 10, 10, 13, 0),
            'costo' => 35, 'pubblicato' => true, 'usa_indirizzo_comitato' => true,
        ]);
        // Il corso deve essere ancora "aperto": spostiamo le date in avanti mantenendo l'ora da muro.
        $this->corso->update(['data_inizio' => now()->addMonth()->setTime(9, 0), 'data_fine' => now()->addMonth()->setTime(13, 0)]);
    }

    private function persona(string $cf, string $nome, string $email): array
    {
        return [
            'nome' => $nome, 'cognome' => 'Test', 'email' => $email, 'telefono' => '333', 'codice_fiscale' => $cf,
            'via' => 'Via Roma 1', 'comune' => 'Bologna', 'provincia' => 'BO', 'cap' => '40100',
        ];
    }

    private function invia(array $referente, array $nominativi, array $extra = [])
    {
        return $this->post('/corsi/blsd-1/iscrizione', array_merge([
            'richiedente' => array_intersect_key($referente, array_flip(['nome', 'cognome', 'email', 'telefono', 'codice_fiscale'])),
            'nominativi' => $nominativi,
            'fatturazione_unica' => 1,
            'fatturazione' => ['tipo' => 'privato', 'nome' => 'Mario', 'cognome' => 'Rossi', 'metodo_pagamento' => 'Bonifico'],
            'privacy_accettata' => 1,
        ], $extra));
    }

    // ---- email di iscrizione

    public function test_iscrizione_da_solo_invia_la_mail_con_calendario_e_link_personale(): void
    {
        Mail::fake();
        $a = $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test');

        $this->invia($a, [$a])->assertRedirect();

        Mail::assertSent(IscrizioneCorsoMail::class, 1);
        Mail::assertSent(IscrizioneCorsoMail::class, function (IscrizioneCorsoMail $m) {
            $this->assertTrue($m->hasTo('mario@example.test'));
            $html = $m->render();
            $this->assertStringContainsString('/preferenze/'.Persona::first()->token, $html);
            $this->assertStringContainsString('Corso BLSD', $html);
            $this->assertStringContainsString('Via Roma 1, Bologna', $html);

            // Reply-To, oggetto e allegato si impostano in build(): lo si chiama una volta sola.
            $m->build();
            $this->assertSame('Iscrizione registrata: Corso BLSD', $m->subject);
            $this->assertTrue($m->hasReplyTo('segreteria@comitato.test'));
            $this->assertCount(1, $m->rawAttachments);
            $this->assertSame('promemoria-corso.ics', $m->rawAttachments[0]['name']);

            return true;
        });
    }

    public function test_referente_che_iscrive_altri_non_riceve_la_mail_ma_ogni_iscritto_si(): void
    {
        Mail::fake();
        $hr = $this->persona(self::ADULTO_A, 'Hilda', 'hr@azienda.test');
        $b = $this->persona(self::ADULTO_B, 'Bruno', 'bruno@azienda.test');
        $c = $this->persona(self::ADULTA_C, 'Carla', 'carla@azienda.test');

        $this->invia($hr, [$b, $c], ['autodichiarazione_terzi' => 1])->assertRedirect();

        Mail::assertSent(IscrizioneCorsoMail::class, 2);
        Mail::assertSent(IscrizioneCorsoMail::class, fn ($m) => $m->hasTo('bruno@azienda.test'));
        Mail::assertSent(IscrizioneCorsoMail::class, fn ($m) => $m->hasTo('carla@azienda.test'));
        Mail::assertNotSent(IscrizioneCorsoMail::class, fn ($m) => $m->hasTo('hr@azienda.test'));
    }

    public function test_la_mail_registra_la_richiesta_di_consenso_solo_dove_manca_una_decisione(): void
    {
        Mail::fake();
        $hr = $this->persona(self::ADULTO_A, 'Hilda', 'hr@azienda.test');
        $b = $this->persona(self::ADULTO_B, 'Bruno', 'bruno@azienda.test');

        // Bruno ha già confermato la newsletter in passato.
        $bruno = Persona::daDati($b);
        $bruno->registraConsenso('newsletter', 'confermato', 'form_pubblico');

        $this->invia($hr, [$b], ['autodichiarazione_terzi' => 1])->assertRedirect();

        $bruno->refresh();
        $this->assertSame('confermato', $bruno->newsletter_stato);   // non declassato
        $this->assertSame('richiesto', $bruno->promemoria_stato);    // mancava: richiesto
        $this->assertSame(['confermato'], $bruno->consensi()->where('finalita', 'newsletter')->pluck('evento')->all());
        $this->assertSame('email_iscrizione', $bruno->consensi()->where('finalita', 'promemoria')->value('origine'));
    }

    public function test_un_invio_fallito_non_fa_perdere_l_iscrizione(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp giù'));
        $a = $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test');

        $this->invia($a, [$a])->assertRedirect(route('corsi.iscrizione.confermata', $this->corso));

        $this->assertSame(1, IscrizioneCorso::count());
        // Senza email partita non si registra nessuna "richiesta di consenso".
        $this->assertNull(Persona::first()->promemoria_stato);
    }

    public function test_iscrizione_a_mano_dall_admin_invia_la_mail(): void
    {
        Mail::fake();
        $a = $this->persona(self::ADULTO_A, 'Mario', 'mario@example.test');

        $this->actingAs(Admin::first(), 'admin')->post(route('admin.corsi.iscritti.store', $this->corso), [
            'nominativo' => $a, 'fatturazione_modo' => 'stessi', 'fatturazione' => ['metodo_pagamento' => 'Bonifico'], 'privacy_confermata' => 1,
        ])->assertRedirect();

        Mail::assertSent(IscrizioneCorsoMail::class, fn ($m) => $m->hasTo('mario@example.test'));
        $this->assertSame('richiesto', Persona::first()->newsletter_stato);
    }

    // ---- template

    public function test_template_sostituisce_i_segnaposto_e_scappa_l_html_dei_valori(): void
    {
        $r = EmailTemplate::render('iscrizione-corso', EmailTemplate::valoriEsempio('iscrizione-corso') + []);
        $this->assertStringNotContainsString('{', $r['corpo']);
        $this->assertStringContainsString('0575 000000', $r['corpo']);   // segnaposto del comitato

        $r = EmailTemplate::render('iscrizione-corso', ['nome' => '<script>alert(1)</script>', 'corso' => "Corso\nBcc: x@y.z"] + EmailTemplate::valoriEsempio('iscrizione-corso'));
        $this->assertStringNotContainsString('<script>', $r['corpo']);
        $this->assertStringContainsString('&lt;script&gt;', $r['corpo']);
        $this->assertStringNotContainsString("\n", $r['oggetto']);       // niente header injection nell'oggetto
    }

    public function test_override_ha_la_precedenza_sul_predefinito_e_si_rimuove(): void
    {
        $admin = Admin::first();

        $this->actingAs($admin, 'admin')->put(route('admin.email-templates.update', 'iscrizione-corso'), [
            'oggetto' => 'Ciao {nome}!', 'corpo' => '<p>Testo mio per {corso}</p>',
        ])->assertRedirect();

        $r = EmailTemplate::render('iscrizione-corso', EmailTemplate::valoriEsempio('iscrizione-corso'));
        $this->assertSame('Ciao Mario!', $r['oggetto']);
        $this->assertStringContainsString('Testo mio per', $r['corpo']);

        $this->actingAs($admin, 'admin')->get(route('admin.email-templates.anteprima', 'iscrizione-corso'))
            ->assertOk()->assertSee('Testo mio per');

        $this->actingAs($admin, 'admin')->delete(route('admin.email-templates.destroy', 'iscrizione-corso'))->assertRedirect();
        $this->assertStringStartsWith('Iscrizione registrata', EmailTemplate::render('iscrizione-corso', EmailTemplate::valoriEsempio('iscrizione-corso'))['oggetto']);
    }

    public function test_l_editor_visuale_non_lascia_http_davanti_ai_segnaposto_dei_link(): void
    {
        $this->actingAs(Admin::first(), 'admin')->put(route('admin.email-templates.update', 'iscrizione-corso'), [
            'oggetto' => 'Ciao',
            'corpo' => '<p><a href="http://{link_preferenze}">uno</a> <a href="https://%7Blink_preferenze%7D">due</a> <a href="https://esempio.it/x">tre</a></p>',
        ])->assertRedirect();

        $corpo = EmailTemplate::where('is_default', false)->value('corpo');
        $this->assertSame(2, substr_count($corpo, 'href="{link_preferenze}"'));
        $this->assertStringContainsString('href="https://esempio.it/x"', $corpo);   // i link veri restano
    }

    public function test_pannello_template_usa_l_editor_visuale(): void
    {
        $this->actingAs(Admin::first(), 'admin')->get(route('admin.email-templates.edit', 'iscrizione-corso'))
            ->assertOk()->assertSee('summernote-bs4.min.js', false)->assertSee('Inserisci dato', false);
    }

    public function test_pannello_template_solo_admin_e_tipo_inesistente_404(): void
    {
        $admin = Admin::first();
        $editor = Admin::create(['name' => 'E', 'email' => 'e@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.email-templates.index'))->assertOk()->assertSee('Iscrizione a un corso');
        $this->actingAs($admin, 'admin')->get(route('admin.email-templates.edit', 'iscrizione-corso'))->assertOk()->assertSee('{link_preferenze}', false);
        $this->actingAs($admin, 'admin')->get(route('admin.email-templates.edit', 'inesistente'))->assertNotFound();
        $this->actingAs($editor, 'admin')->get(route('admin.email-templates.index'))->assertForbidden();
    }

    public function test_pulsante_ripristina_offre_il_testo_del_sorgente_senza_salvare(): void
    {
        $sorgente = EmailTemplate::testoPredefinito('iscrizione-corso');

        // Il default seminato coincide con il sorgente; anche se il DB venisse alterato, il ripristino usa il sorgente.
        $this->assertSame($sorgente['corpo'], EmailTemplate::where('is_default', true)->value('corpo'));
        EmailTemplate::where('is_default', true)->update(['corpo' => '<p>alterato</p>']);

        $pagina = $this->actingAs(Admin::first(), 'admin')->get(route('admin.email-templates.edit', 'iscrizione-corso'))
            ->assertOk()->assertSee('Ripristina il testo predefinito')->assertSee('Testo predefinito caricato', false);

        // Il sorgente è nella pagina (JSON) per il pulsante; la sola visita non crea nessuna personalizzazione.
        $pagina->assertSee('Iscrizione registrata: {corso}', false);
        $this->assertSame(0, EmailTemplate::where('is_default', false)->count());
        $this->assertNull(EmailTemplate::testoPredefinito('inesistente'));
    }

    public function test_il_link_di_conferma_e_un_pulsante_rosso_ben_visibile(): void
    {
        $r = EmailTemplate::render('iscrizione-corso', EmailTemplate::valoriEsempio('iscrizione-corso'), EmailTemplate::grezziEsempio('iscrizione-corso'));

        $this->assertStringContainsString('bgcolor="#cc0000"', $r['corpo']);
        $this->assertStringContainsString('Conferma la tua presenza</a>', $r['corpo']);
        $this->assertMatchesRegularExpression('/<a href="[^"]*preferenze[^"]*"[^>]*background|<a href="[^"]*preferenze[^"]*" style="display:inline-block/', $r['corpo']);
    }

    // ---- calendario

    public function test_ics_ha_orari_in_utc_dall_ora_locale_luogo_escapato_e_righe_corte(): void
    {
        $this->corso->update([
            'data_inizio' => Carbon::create(2026, 10, 10, 9, 0), 'data_fine' => Carbon::create(2026, 10, 10, 13, 0), // CEST: UTC+2
            'usa_indirizzo_comitato' => false,
        ]);
        $sede = \App\Models\SedeCorso::create(['nome' => 'Sala "Rossa"; piano 2, scala B — una sede con un nome molto molto lungo per forzare la piegatura della riga', 'via' => 'x', 'comune' => 'y', 'provincia' => 'BO', 'cap' => '40100']);
        $this->corso->update(['sede_corso_id' => $sede->id]);

        $persona = Persona::daDati(['nome' => 'M', 'cognome' => 'R', 'codice_fiscale' => self::ADULTO_A, 'email' => 'm@example.test']);
        $iscrizione = IscrizioneCorso::create([
            'corso_id' => $this->corso->id, 'dati_fatturazione_corso_id' => \App\Models\DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => 'M', 'cognome' => 'R'])->id,
            'persona_id' => $persona->id, 'nome' => 'M', 'cognome' => 'R', 'email' => 'm@example.test', 'codice_fiscale' => self::ADULTO_A,
            'privacy_accettata_at' => now(),
        ]);

        $ics = IcsEvento::da($iscrizione->fresh());

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertStringContainsString("DTSTART:20261010T070000Z\r\n", $ics);   // 09:00 a Roma = 07:00 UTC
        $this->assertStringContainsString("DTEND:20261010T110000Z\r\n", $ics);
        $this->assertStringContainsString('SUMMARY:Corso BLSD', $ics);
        $this->assertStringContainsString('TRIGGER:-P1D', $ics);
        $this->assertStringContainsString('\\;', $ics);   // il punto e virgola della sede è escapato
        $this->assertStringContainsString('\\,', $ics);
        foreach (explode("\r\n", rtrim($ics)) as $riga) {
            $this->assertLessThanOrEqual(75, strlen($riga), "riga troppo lunga: {$riga}");
        }
    }

    // ---- pagina personale

    private function personaConToken(): Persona
    {
        return Persona::daDati(['nome' => 'Mario', 'cognome' => 'Rossi', 'codice_fiscale' => self::ADULTO_A, 'email' => 'mario@example.test']);
    }

    public function test_pagina_personale_token_sconosciuto_o_malformato_e_404(): void
    {
        $this->get('/preferenze/'.str_repeat('a', 64))->assertNotFound();
        $this->get('/preferenze/corto')->assertNotFound();
        $this->get('/preferenze/'.str_repeat('a', 63).'!')->assertNotFound();
    }

    public function test_pagina_personale_non_indicizzabile_ne_in_cache(): void
    {
        $p = $this->personaConToken();

        $r = $this->get(route('preferenze.show', $p->token))->assertOk()->assertSee('Ciao Mario');

        $r->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $r->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $r->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_robots_in_produzione_blocca_le_pagine_personali(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->get('/robots.txt')->assertSee('Disallow: /preferenze/');
    }

    public function test_dopo_il_salvataggio_pagina_di_conferma_senza_modulo_poi_di_nuovo_completa(): void
    {
        $p = $this->personaConToken();

        $conferma = $this->followingRedirects()->post(route('preferenze.consensi', $p->token), ['consenso_newsletter' => 1]);
        $conferma->assertOk()
            ->assertSee('Le tue scelte sono state salvate.')
            ->assertSee('I tuoi corsi')
            ->assertDontSee('Salva le mie scelte')
            ->assertDontSee('Conferma presenza e salva')
            ->assertDontSee('Chiedi la cancellazione')
            ->assertDontSee('name="consenso_newsletter"', false);

        // Riaprendo il link (nessun messaggio in sessione) si torna alla pagina completa, con le scelte salvate.
        $this->get(route('preferenze.show', $p->token))->assertSee('Salva le mie scelte')->assertSee('Chiedi la cancellazione');
    }

    public function test_la_persona_concede_e_revoca_i_consensi_dalla_sua_pagina(): void
    {
        $p = $this->personaConToken();

        $this->post(route('preferenze.consensi', $p->token), ['consenso_newsletter' => 1])->assertRedirect();
        $p->refresh();
        $this->assertTrue($p->haConsenso('newsletter'));
        $this->assertFalse($p->haConsenso('promemoria'));
        $this->assertSame('link_personale', $p->consensi()->where('finalita', 'newsletter')->value('origine'));

        // Salvare di nuovo senza cambiare nulla non crea eventi doppi.
        $this->post(route('preferenze.consensi', $p->token), ['consenso_newsletter' => 1]);
        $this->assertSame(1, $p->consensi()->count());

        // Togliere la spunta = revoca.
        $this->post(route('preferenze.consensi', $p->token), [])->assertRedirect();
        $this->assertSame('revocato', $p->fresh()->newsletter_stato);
        $this->assertSame(['confermato', 'revocato'], $p->consensi()->orderBy('id')->pluck('evento')->all());
    }

    public function test_conferma_presenza_ai_corsi_in_programma_e_non_a_quelli_passati_o_annullati(): void
    {
        $p = $this->personaConToken();
        $fatt = \App\Models\DatiFatturazioneCorso::create(['tipo' => 'privato', 'nome' => 'M', 'cognome' => 'R']);
        $iscrivi = fn (Corso $c) => IscrizioneCorso::create([
            'corso_id' => $c->id, 'dati_fatturazione_corso_id' => $fatt->id, 'persona_id' => $p->id, 'nome' => 'Mario', 'cognome' => 'Rossi',
            'email' => 'mario@example.test', 'codice_fiscale' => self::ADULTO_A, 'privacy_accettata_at' => now(),
        ]);
        $nuovo = fn (string $slug, array $extra) => Corso::create(array_merge([
            'tipologia_corso_id' => $this->corso->tipologia_corso_id, 'slug' => $slug, 'protocollo' => strtoupper($slug), 'admin_id' => Admin::first()->id,
            'data_inizio' => now()->addDays(3), 'data_fine' => now()->addDays(3)->addHours(4), 'costo' => 0, 'pubblicato' => true,
        ], $extra));

        $inProgramma = $iscrivi($this->corso);
        $passato = $iscrivi($nuovo('passato', ['data_inizio' => now()->subDays(9), 'data_fine' => now()->subDays(9)->addHours(4)]));
        $annullato = $iscrivi($nuovo('annullato', ['annullato_at' => now()]));

        $this->get(route('preferenze.show', $p->token))->assertSee('Conferma presenza e salva')->assertSee('Presenza da confermare')->assertSee('Corso annullato');

        $this->post(route('preferenze.consensi', $p->token), [])->assertRedirect();

        $this->assertNotNull($inProgramma->fresh()->presenza_confermata_at);
        $this->assertNull($passato->fresh()->presenza_confermata_at);
        $this->assertNull($annullato->fresh()->presenza_confermata_at);

        // Subito dopo il salvataggio c'è la pagina di conferma (solo dati e messaggio)…
        $this->get(route('preferenze.show', $p->token))->assertSee('Presenza confermata il')->assertDontSee('Conferma presenza e salva');
        // …poi la pagina completa: non c'è più nulla da confermare, il pulsante torna a "Salva le mie scelte".
        $this->get(route('preferenze.show', $p->token))->assertSee('Presenza confermata il')->assertSee('Salva le mie scelte')->assertDontSee('Conferma presenza e salva');
    }

    public function test_richiesta_di_cancellazione_revoca_i_consensi_e_non_manda_email(): void
    {
        Mail::fake();
        $p = $this->personaConToken();
        $p->registraConsenso('newsletter', 'confermato', 'form_pubblico');
        $p->registraConsenso('promemoria', 'confermato', 'form_pubblico');

        $this->post(route('preferenze.cancellazione', $p->token))->assertRedirect();
        $prima = $p->fresh()->richiesta_cancellazione_at;
        $this->post(route('preferenze.cancellazione', $p->token))->assertRedirect();   // ripetuta: la data della prima resta
        $this->assertEquals($prima, $p->fresh()->richiesta_cancellazione_at);

        $p->refresh();
        $this->assertNotNull($p->richiesta_cancellazione_at);
        $this->assertSame('revocato', $p->newsletter_stato);
        $this->assertSame('revocato', $p->promemoria_stato);
        Mail::assertNothingSent();   // nessun avviso agli admin: la richiesta si vede nel pannello Persone

        $this->get(route('preferenze.show', $p->token))->assertSee('Hai chiesto la cancellazione');
    }

    public function test_pagina_personale_con_throttle_per_ip(): void
    {
        $p = $this->personaConToken();

        foreach (range(1, 20) as $_) {
            $this->get(route('preferenze.show', $p->token))->assertOk();
        }
        $this->get(route('preferenze.show', $p->token))->assertStatus(429);
    }
}
