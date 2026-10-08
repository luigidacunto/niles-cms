<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Admin;
use App\Models\LoginAudit;
use App\Models\Member;
use Database\Seeders\FirstInstallSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuditAndDeactivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\CommitteeInfo::current()->update(['area_soci_attiva' => true]); // l'area soci è spenta di default
    }

    private function member(array $extra = []): Member
    {
        return Member::create($extra + [
            'codice_fiscale' => 'AAAAAA00A00A000A', 'nome' => 'Anna', 'cognome' => 'Rossi',
            'ruolo' => Member::RUOLO_VOLONTARIO, 'email' => 'anna@example.test',
        ]);
    }

    private function admin(string $role = 'admin', array $permissions = []): Admin
    {
        return Admin::create(['name' => 'Ada', 'email' => $role.'@example.test', 'password' => 'x', 'role' => $role, 'active' => true, 'permissions' => $permissions]);
    }

    public function test_login_socio_registra_riuscito_e_fallito_con_ip(): void
    {
        $this->member();
        Mail::fake();
        $this->post(route('soci.login.send'), ['email' => 'anna@example.test']);
        $code = Mail::sent(OtpMail::class)->first()->code;

        $this->post(route('soci.login.confirm'), ['code' => $code === '000000' ? '111111' : '000000']);
        $this->post(route('soci.login.confirm'), ['code' => $code]);

        $this->assertSame(['failed', 'success'], LoginAudit::orderBy('id')->pluck('event')->all());
        $row = LoginAudit::where('event', 'success')->first();
        $this->assertSame('member', $row->guard);
        $this->assertSame('Rossi Anna', $row->label);
        $this->assertNotNull($row->ip);
    }

    public function test_login_admin_password_registra_esito(): void
    {
        $this->admin();
        $this->post(route('admin.login.password.confirm'), ['email' => 'nessuno@example.test', 'password' => 'x']);

        $row = LoginAudit::first();
        $this->assertSame(['admin', 'failed', null, 'nessuno@example.test'], [$row->guard, $row->event, $row->subject_id, $row->label]);
    }

    public function test_righe_oltre_12_mesi_vengono_cancellate_alla_scrittura_successiva(): void
    {
        $vecchia = LoginAudit::create(['guard' => 'member', 'event' => 'success', 'label' => 'old']);
        $vecchia->forceFill(['created_at' => now()->subMonths(13)])->save();

        $this->post(route('admin.login.password.confirm'), ['email' => 'x@example.test', 'password' => 'x']);

        $this->assertNull(LoginAudit::where('label', 'old')->first());
        $this->assertSame(1, LoginAudit::count());
    }

    public function test_registro_accessi_solo_admin(): void
    {
        $this->actingAs($this->admin('editor', ['membri' => ['read', 'write']]), 'admin')
            ->get(route('admin.registro-accessi'))->assertForbidden();

        LoginAudit::create(['guard' => 'member', 'event' => 'success', 'label' => 'Rossi Anna', 'ip' => '10.0.0.1']);
        $this->actingAs($this->admin(), 'admin')->get(route('admin.registro-accessi'))->assertOk()->assertSee('Rossi Anna')->assertSee('10.0.0.1');
    }

    public function test_socio_disabilitato_mentre_e_loggato_perde_la_sessione(): void
    {
        $m = $this->member();
        $this->actingAs($m, 'member')->get(route('soci.profilo'))->assertOk();

        $m->update(['disabilitato' => true]);

        $this->get(route('soci.profilo'))->assertRedirect(route('soci.login'));
    }

    public function test_richiesta_disattivazione_poi_conferma_admin(): void
    {
        $m = $this->member();
        $this->actingAs($m, 'member')->post(route('soci.profilo.disattivazione'))->assertRedirect(route('soci.profilo'));
        $this->get(route('soci.profilo'))->assertSee('Hai chiesto la disattivazione');
        $this->assertNotNull($m->fresh()->richiesta_disattivazione_at);
        $this->assertFalse($m->fresh()->disabilitato); // non è automatica: serve l'admin

        auth('member')->logout();
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.membri.index', ['presenza' => 'richieste']))->assertOk()->assertSee('Disattivazione richiesta');

        $this->put(route('admin.membri.update', $m), [
            'codice_fiscale' => $m->codice_fiscale, 'nome' => 'Anna', 'cognome' => 'Rossi', 'ruolo' => $m->ruolo, 'disabilitato' => '1',
        ])->assertRedirect(route('admin.membri.index'));

        $m->refresh();
        $this->assertTrue($m->disabilitato);
        $this->assertNull($m->richiesta_disattivazione_at);
    }

    public function test_admin_puo_respingere_la_richiesta(): void
    {
        $m = $this->member();
        $m->forceFill(['richiesta_disattivazione_at' => now()])->save();

        $this->actingAs($this->admin(), 'admin')->put(route('admin.membri.respingi-richiesta', $m))->assertRedirect();

        $this->assertNull($m->fresh()->richiesta_disattivazione_at);
        $this->assertFalse($m->fresh()->disabilitato);
    }

    public function test_informativa_soci_raggiungibile(): void
    {
        $this->seed(FirstInstallSeeder::class);

        $this->get(route('privacy-policy.show', 'soci'))->assertOk()->assertSee('indirizzo IP');
        $this->get(route('soci.login'))->assertSee(route('privacy-policy.show', 'soci'), false);
    }
}
