<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\CommitteeInfo::current()->update(['area_soci_attiva' => true]); // l'area soci è spenta di default
    }

    private \Illuminate\Testing\TestResponse $lastResponse;

    private function member(array $extra = []): Member
    {
        return Member::create($extra + [
            'codice_fiscale' => 'AAAAAA00A00A000A', 'nome' => 'Anna', 'cognome' => 'Rossi',
            'ruolo' => Member::RUOLO_VOLONTARIO, 'email' => 'anna@example.test',
        ]);
    }

    /** Richiede il codice e lo cattura dalla mail inviata. */
    private function requestCode(string $route, string $email): ?string
    {
        Mail::fake();
        $this->lastResponse = $this->post(route($route), ['email' => $email]);
        $sent = Mail::sent(OtpMail::class);

        return $sent->isEmpty() ? null : $sent->first()->code;
    }

    public function test_membro_accede_con_otp_e_torna_alla_home(): void
    {
        $this->member();
        $code = $this->requestCode('soci.login.send', 'Anna@Example.test');
        $this->assertNotNull($code);

        $this->post(route('soci.login.confirm'), ['code' => $code])->assertRedirect(route('home'));
        $this->assertAuthenticated('member');
    }

    public function test_codice_errato_o_riusato_non_accede(): void
    {
        $this->member();
        $code = $this->requestCode('soci.login.send', 'anna@example.test');

        $this->post(route('soci.login.confirm'), ['code' => $code === '000000' ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->assertGuest('member');

        $this->post(route('soci.login.confirm'), ['code' => $code])->assertRedirect(route('home'));
        auth('member')->logout();
        $this->post(route('soci.login.confirm'), ['code' => $code])->assertSessionHasErrors('code'); // monouso
    }

    public function test_disabilitato_rimosso_o_sconosciuto_non_ricevono_codice_ma_stessa_risposta(): void
    {
        $this->member(['disabilitato' => true]);
        $this->member(['codice_fiscale' => 'BBBBBB00B00B000B', 'email' => 'gone@example.test'])->rimuovi();

        foreach (['anna@example.test', 'gone@example.test', 'nessuno@example.test'] as $email) {
            $this->assertNull($this->requestCode('soci.login.send', $email));
            $this->lastResponse->assertRedirect(route('soci.login.verify'));
        }
    }

    public function test_profilo_richiede_login_e_mostra_i_dati_del_socio(): void
    {
        $this->get(route('soci.profilo'))->assertRedirect(route('soci.login'));

        $m = $this->member(['telefono' => '0575 1', 'telefoni_aggiuntivi' => ['333 2']]);
        $this->actingAs($m, 'member')->get(route('soci.profilo'))
            ->assertOk()->assertSee('Rossi')->assertSee('AAAAAA00A00A000A')->assertSee('0575 1')->assertSee('333 2');
    }

    public function test_pulsante_in_testata_ospite_vs_socio_e_login_gia_fatto_non_va_in_admin(): void
    {
        $this->get(route('home'))->assertSee(route('soci.login'), false)->assertDontSee('I miei dati');
        $this->get(route('soci.login'))->assertOk()->assertSee('solo ai soci del comitato');

        $this->actingAs($this->member(), 'member');
        $this->get(route('home'))->assertSee('I miei dati')->assertSee(route('soci.logout'), false);
        $this->get(route('soci.login'))->assertRedirect(route('home'));
    }

    public function test_login_admin_con_otp_resta_funzionante(): void
    {
        Admin::create(['name' => 'A', 'email' => 'adm@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true, 'permissions' => []]);
        $code = $this->requestCode('admin.login.otp.send', 'adm@example.test');
        $this->assertNotNull($code);

        $this->post(route('admin.login.otp.confirm'), ['code' => $code])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated('admin');
    }
}
