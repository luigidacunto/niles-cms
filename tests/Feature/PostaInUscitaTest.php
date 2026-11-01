<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Support\PostaInUscita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PostaInUscitaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => [],
        ]);
    }

    private function dati(array $extra = []): array
    {
        return $extra + [
            'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.org', 'mail_port' => 465, 'mail_encryption' => 'ssl',
            'mail_username' => 'no-reply@example.org', 'mail_password' => 'segreta-123',
            'mail_from_address' => '', 'mail_from_name' => 'Comitato',
        ];
    }

    public function test_precedenza_env_database_log(): void
    {
        CommitteeInfo::current()->update($this->dati());

        // Una sola variabile MAIL_* nel .env: il pannello è ignorato del tutto.
        config(['mail.env_presenti' => ['MAIL_MAILER'], 'mail.default' => 'log']);
        PostaInUscita::applica();
        $this->assertSame('log', config('mail.default'));

        // Nessuna variabile nel .env: vale il database.
        config(['mail.env_presenti' => []]);
        PostaInUscita::applica();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.org', config('mail.mailers.smtp.host'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('segreta-123', config('mail.mailers.smtp.password'));
        $this->assertSame('no-reply@example.org', config('mail.from.address')); // mittente vuoto = utente SMTP
        $this->assertSame('Comitato', config('mail.from.name'));
    }

    public function test_senza_nulla_resta_il_predefinito(): void
    {
        config(['mail.env_presenti' => [], 'mail.default' => 'log']);
        PostaInUscita::applica();

        $this->assertSame('log', config('mail.default'));
    }

    public function test_env_incompleto_elenca_le_variabili_mancanti(): void
    {
        config(['mail.env_presenti' => ['MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT'], 'mail.default' => 'smtp', 'mail.mailers.smtp.port' => 465]);

        $this->assertEqualsCanonicalizing(['MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS', 'MAIL_SCHEME'], PostaInUscita::envMancanti());

        $this->actingAs($this->admin(), 'admin')->get(route('admin.posta.edit'))
            ->assertOk()->assertSee('Configurazione .env incompleta')->assertSee('MAIL_USERNAME');

        config(['mail.env_presenti' => ['MAIL_MAILER'], 'mail.default' => 'log']);
        $this->assertSame([], PostaInUscita::envMancanti());
    }

    public function test_password_cifrata_e_mai_mostrata(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.posta.update'), $this->dati())
            ->assertRedirect(route('admin.posta.edit'));

        $grezza = DB::table('committee_info')->value('mail_password');
        $this->assertNotSame('segreta-123', $grezza);
        $this->assertSame('segreta-123', CommitteeInfo::current()->mail_password);
        $this->assertSame('Tester', CommitteeInfo::current()->mail_modificata_da);

        config(['mail.env_presenti' => []]);
        $this->get(route('admin.posta.edit'))->assertOk()->assertDontSee('segreta-123')->assertSee('smtp.example.org');

        // Password vuota nel modulo = tieni quella salvata.
        $this->put(route('admin.posta.update'), $this->dati(['mail_password' => '', 'mail_host' => 'altro.example.org']));
        $this->assertSame('segreta-123', CommitteeInfo::current()->mail_password);
        $this->assertSame('altro.example.org', CommitteeInfo::current()->mail_host);
    }

    public function test_password_non_decifrabile_ripiega_e_la_pagina_lo_segnala(): void
    {
        CommitteeInfo::current()->update($this->dati());
        DB::table('committee_info')->update(['mail_password' => 'non-decifrabile']);
        config(['mail.env_presenti' => [], 'mail.default' => 'log']);

        PostaInUscita::applica();
        $this->assertSame('log', config('mail.default'));

        $this->actingAs($this->admin(), 'admin')->get(route('admin.posta.edit'))->assertOk()->assertSee('la password non si può leggere');
    }

    public function test_tabella_senza_colonne_non_rompe_lavvio(): void
    {
        DB::statement('DROP TABLE committee_info');
        config(['mail.env_presenti' => [], 'mail.default' => 'log']);

        PostaInUscita::applica();

        $this->assertSame('log', config('mail.default'));
    }

    public function test_accessi(): void
    {
        $this->get(route('admin.posta.edit'))->assertRedirect();
        $this->actingAs($this->admin('editor'), 'admin')->get(route('admin.posta.edit'))->assertForbidden();
        $this->put(route('admin.posta.prova'), $this->dati())->assertForbidden();
    }

    public function test_demo_blocca_il_salvataggio_e_mostra_dati_fasulli(): void
    {
        config(['app.demo' => true]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get(route('admin.posta.edit'))->assertOk()->assertSee('smtp.example.org')->assertSee('Versione dimostrativa');

        $this->from(route('admin.posta.edit'))->put(route('admin.posta.update'), $this->dati());
        $this->assertNull(CommitteeInfo::current()->mail_mailer);
    }

    public function test_prova_con_valori_del_modulo_solo_allamministratore(): void
    {
        Mail::shouldReceive('build')->once()->withArgs(fn ($c) => $c['host'] === 'smtp.example.org' && $c['password'] === 'segreta-123' && $c['scheme'] === 'smtps')
            ->andReturn($mailer = \Mockery::mock());
        $mailer->shouldReceive('raw')->once()->andReturnUsing(function ($testo, $cb) {
            $m = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email);
            $cb($m);
            $this->assertSame('admin@example.test', $m->getSymfonyMessage()->getTo()[0]->getAddress());
            $this->assertSame('persona@example.org', $m->getSymfonyMessage()->getReplyTo()[0]->getAddress()); // anche non salvato
        });

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.posta.prova'), $this->dati(['mail_to' => 'altro@example.test', 'mail_rispondi_a' => 'persona@example.org']))
            ->assertSessionHas('status');

        $info = CommitteeInfo::current();
        $this->assertSame('ok', $info->mail_prova_esito);
        $this->assertArrayNotHasKey('password', $info->mail_prova_parametri);
        $this->assertNull($info->mail_mailer); // la prova non salva le impostazioni
    }

    public function test_errore_smtp_nella_prova_diventa_un_messaggio(): void
    {
        Mail::shouldReceive('build')->once()->andThrow(new \RuntimeException('Connessione rifiutata'));

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.posta.prova'), $this->dati())
            ->assertSessionHas('prova_errore');

        $this->assertSame('Connessione rifiutata', CommitteeInfo::current()->mail_prova_esito);
        $this->get(route('admin.posta.edit'))->assertOk()->assertSee('non riuscita');
    }

    public function test_rispondi_a_del_pannello_vale_anche_con_il_env_e_ripiega_sull_email_del_comitato(): void
    {
        // Senza nulla di salvato: nessun Reply-To.
        config(['mail.reply_to' => null, 'mail.env_presenti' => ['MAIL_MAILER']]);
        PostaInUscita::applicaRispondiA();
        $this->assertNull(config('mail.reply_to'));

        // Solo l'email del comitato: è il ripiego.
        CommitteeInfo::current()->update(['email' => 'segreteria@example.org']);
        PostaInUscita::applica(); // ⚠️ con una variabile nel .env il server resta quello del .env, il «Rispondi a» no
        $this->assertSame('segreteria@example.org', config('mail.reply_to.address'));

        // Quello del pannello ha la precedenza.
        CommitteeInfo::current()->update(['mail_rispondi_a' => 'persona@example.org']);
        PostaInUscita::applica();
        $this->assertSame('persona@example.org', config('mail.reply_to.address'));
        $this->assertSame(config('app.public_name'), config('mail.reply_to.name'));
    }

    public function test_il_codice_di_accesso_parte_con_il_rispondi_a(): void
    {
        CommitteeInfo::current()->update(['mail_rispondi_a' => 'persona@example.org']);
        config(['mail.default' => 'array', 'mail.env_presenti' => ['MAIL_MAILER']]);
        PostaInUscita::applica();
        Mail::forgetMailers();

        Mail::to('x@example.test')->send(new OtpMail('123456'));

        $inviato = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame('persona@example.org', $inviato->getReplyTo()[0]->getAddress());
    }

    public function test_email_ai_corsisti_rispondono_al_primo_indirizzo_della_formazione_con_un_solo_reply_to(): void
    {
        CommitteeInfo::current()->update(['email' => 'comitato@example.org', 'mail_rispondi_a' => 'generale@example.org']);
        config(['mail.default' => 'array', 'mail.env_presenti' => ['MAIL_MAILER']]);
        $corsista = new class extends \Illuminate\Mail\Mailable
        {
            public function build(): static
            {
                PostaInUscita::rispondiAFormazione($this);

                return $this->subject('x')->html('x');
            }
        };
        $replyTo = function () use ($corsista) {
            PostaInUscita::applica();
            Mail::forgetMailers();
            Mail::to('x@example.test')->send($corsista);

            return collect(Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getReplyTo())
                ->map->getAddress()->all();
        };

        // Senza indirizzi della formazione: solo il «Rispondi a» generale.
        $this->assertSame(['generale@example.org'], $replyTo());

        // Con l'elenco: il primo indirizzo valido, e uno solo (non si somma al generale).
        CommitteeInfo::current()->update(['mail_notifica_iscrizioni_a' => "formazione@example.org\naltro@example.org"]);
        Mail::forgetMailers();
        $this->assertSame(['formazione@example.org'], $replyTo());
    }

    public function test_notifica_iscrizioni_si_salva_normalizzata_e_si_valida(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->put(route('admin.posta.update'), ['mail_mailer' => 'log', 'mail_rispondi_a' => 'persona@example.org',
            'mail_notifica_iscrizioni' => '1', 'mail_notifica_iscrizioni_a' => "a@example.org, b@example.org;\n a@example.org"])
            ->assertSessionHasNoErrors();
        $info = CommitteeInfo::current();
        $this->assertTrue($info->mail_notifica_iscrizioni);
        $this->assertSame("a@example.org\nb@example.org", $info->mail_notifica_iscrizioni_a);
        $this->assertSame(['a@example.org', 'b@example.org'], PostaInUscita::destinatariNotificaIscrizioni());

        // Spunta senza indirizzi, o con un indirizzo non valido: errore.
        $this->put(route('admin.posta.update'), ['mail_mailer' => 'log', 'mail_notifica_iscrizioni' => '1'])
            ->assertSessionHasErrors('mail_notifica_iscrizioni_a');
        $this->put(route('admin.posta.update'), ['mail_mailer' => 'log', 'mail_notifica_iscrizioni' => '1', 'mail_notifica_iscrizioni_a' => 'non-una-email'])
            ->assertSessionHasErrors('mail_notifica_iscrizioni_a');

        // Spunta spenta: nessun destinatario, gli indirizzi restano salvati.
        $this->put(route('admin.posta.update'), ['mail_mailer' => 'log', 'mail_notifica_iscrizioni' => '0', 'mail_notifica_iscrizioni_a' => 'a@example.org']);
        $this->assertSame([], PostaInUscita::destinatariNotificaIscrizioni());
    }

    public function test_validazione(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.posta.update'), $this->dati(['mail_host' => '', 'mail_port' => 99999]))
            ->assertSessionHasErrors(['mail_host', 'mail_port']);

        // Con «disattivata» i campi SMTP non servono.
        $this->put(route('admin.posta.update'), ['mail_mailer' => 'log'])->assertSessionHasNoErrors();
        $this->assertSame('log', CommitteeInfo::current()->mail_mailer);
    }
}
