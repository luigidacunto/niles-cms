<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Corso;
use App\Models\Page;
use App\Models\PrivacyPolicy;
use App\Models\SicurezzaForm;
use App\Models\TipologiaCorso;
use App\Rules\TurnstileToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CorsiFormazioneTest extends TestCase
{
    use RefreshDatabase;

    private function corso(TipologiaCorso $tipologia, string $protocollo, array $extra = []): Corso
    {
        return Corso::create(array_merge([
            'tipologia_corso_id' => $tipologia->id, 'slug' => strtolower($protocollo), 'protocollo' => $protocollo,
            'data_inizio' => now()->addDays(10), 'data_fine' => now()->addDays(10)->addHours(4),
            'costo' => 35, 'pubblicato' => true,
            'admin_id' => Admin::firstOrCreate(['email' => 'a@example.test'], ['name' => 'A', 'password' => 'x', 'role' => 'admin', 'active' => true])->id,
        ], $extra));
    }

    public function test_pagina_template_elenca_solo_corsi_con_iscrizioni_aperte(): void
    {
        Page::create(['slug' => 'corsi-di-formazione', 'title' => 'Corsi di Formazione', 'body' => '<p>x</p>',
            'published' => true, 'system' => true, 'template' => 'corsi']);
        $blsd = TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD', 'immagine' => 'corsi/tipologie/blsd.jpg']);
        $this->corso($blsd, 'BLSD-2026-001');
        $this->corso($blsd, 'BLSD-2026-002', ['annullato_at' => now()]);
        $this->corso($blsd, 'BLSD-2026-003', ['pubblicato' => false]);
        $this->corso($blsd, 'BLSD-2026-004', ['data_inizio' => now()->subDays(5), 'data_fine' => now()->subDays(5)->addHours(4)]);

        $html = $this->get('/corsi-di-formazione')->assertOk()->assertSee('Corso BLSD')
            ->assertSee('/storage/corsi/tipologie/blsd.jpg')->getContent();

        $this->assertSame(1, substr_count($html, 'Iscriviti'));
        $this->assertStringContainsString('/corsi/blsd-2026-001/iscrizione', $html);
    }

    public function test_pagina_template_senza_corsi_mostra_messaggio(): void
    {
        Page::create(['slug' => 'corsi-di-formazione', 'title' => 'Corsi', 'body' => '<p>x</p>',
            'published' => true, 'system' => true, 'template' => 'corsi']);

        $this->get('/corsi-di-formazione')->assertOk()->assertSee('Al momento non ci sono corsi');
    }

    public function test_pagina_iscrizione_usa_immagine_tipologia_per_og(): void
    {
        $t = TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD', 'immagine' => 'corsi/tipologie/blsd.jpg']);
        $this->corso($t, 'BLSD-2026-001');

        $this->get('/corsi/blsd-2026-001/iscrizione')->assertOk()
            ->assertSee('property="og:image" content="'.url('/storage/corsi/tipologie/blsd.jpg').'"', false);
    }

    private function turnstile(array $input): bool
    {
        return Validator::make($input, ['cf-turnstile-response' => [new TurnstileToken]])->passes();
    }

    private function attiva(): void
    {
        SicurezzaForm::current()->update(['captcha_attivo' => true, 'turnstile_site_key' => 'site', 'turnstile_secret_key' => 'secret']);
    }

    public function test_turnstile_blocca_solo_se_cloudflare_risponde_negativo(): void
    {
        $this->attiva();

        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->assertFalse($this->turnstile(['cf-turnstile-response' => 'tok']));
        $this->assertTrue($this->turnstile(['cf-turnstile-response' => 'tok']));
    }

    public function test_turnstile_fail_open_se_cloudflare_non_risponde(): void
    {
        $this->attiva();

        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push('', 503)->pushFailedConnection()]);

        $this->assertTrue($this->turnstile(['cf-turnstile-response' => 'tok'])); // 503
        $this->assertTrue($this->turnstile(['cf-turnstile-response' => 'tok'])); // rete giù
    }

    public function test_turnstile_ignorato_se_spento_o_senza_chiavi(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->assertTrue($this->turnstile(['cf-turnstile-response' => 'tok']));

        SicurezzaForm::current()->update(['captcha_attivo' => true]); // acceso ma senza chiavi
        $this->assertTrue($this->turnstile(['cf-turnstile-response' => 'tok']));
        Http::assertNothingSent();
    }

    public function test_secret_turnstile_salvata_cifrata(): void
    {
        $this->attiva();

        $this->assertSame('secret', SicurezzaForm::current()->turnstile_secret_key);
        $this->assertNotSame('secret', \DB::table('sicurezza_form')->value('turnstile_secret_key'));
    }

    public function test_informativa_corsi_cita_turnstile_solo_se_attivo(): void
    {
        PrivacyPolicy::create(['tipo' => 'corsi-popolazione', 'is_default' => true, 'titolo' => 'Informativa', 'testo' => '<p>base</p>']);

        $this->get('/privacy/corsi-popolazione')->assertOk()->assertSee('base')->assertDontSee('Cloudflare Turnstile');

        $this->attiva();
        $this->get('/privacy/corsi-popolazione')->assertOk()->assertSee('Cloudflare Turnstile');

        // Anche con un override personalizzato la sezione resta.
        PrivacyPolicy::create(['tipo' => 'corsi-popolazione', 'is_default' => false, 'titolo' => 'Mia', 'testo' => '<p>custom</p>']);
        $this->get('/privacy/corsi-popolazione')->assertSee('custom')->assertSee('Cloudflare Turnstile');
    }

    public function test_form_iscrizione_mostra_widget_e_rimando_solo_se_turnstile_attivo(): void
    {
        $t = TipologiaCorso::create(['nome' => 'Corso BLSD', 'sigla' => 'BLSD']);
        $this->corso($t, 'BLSD-2026-001');

        $this->get('/corsi/blsd-2026-001/iscrizione')->assertOk()->assertDontSee('cf-turnstile');

        $this->attiva();
        $this->get('/corsi/blsd-2026-001/iscrizione')->assertOk()->assertSee('cf-turnstile')->assertSee('privacy/corsi-popolazione#captcha', false);
    }
}
