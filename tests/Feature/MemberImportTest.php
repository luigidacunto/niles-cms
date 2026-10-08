<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Services\MemberImportService as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class MemberImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\CommitteeInfo::current()->update(['area_soci_attiva' => true]); // l'area soci è spenta di default
    }

    /** @param array<int, array{0:string,1:string,2:string,3:string}> $rows [cf, nome, tipo, telefoni] */
    private function xlsx(array $rows): string
    {
        $sheet = ($wb = new Spreadsheet)->getActiveSheet();
        $sheet->fromArray(['Cognome', 'Nome', 'Codice Fiscale', 'Data di Nascita', 'Email', 'Numeri di telefono', 'Tipo Attuale'], null, 'A1');
        foreach ($rows as $i => [$cf, $nome, $tipo, $tel]) {
            $sheet->fromArray(['Rossi', $nome, $cf, '09/07/1986', strtolower($nome).'@example.test', $tel, $tipo], null, 'A'.($i + 2));
        }
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($wb))->save($path);

        return $path;
    }

    private function member(string $cf, string $ruolo, array $extra = []): Member
    {
        return Member::create(['codice_fiscale' => $cf, 'nome' => 'X', 'cognome' => 'Y', 'ruolo' => $ruolo] + $extra);
    }

    private const CF1 = 'AAAAAA00A00A000A';
    private const CF2 = 'BBBBBB00B00B000B';
    private const CF3 = 'CCCCCC00C00C000C';

    public function test_sync_per_lista_non_tocca_le_altre_liste(): void
    {
        $this->member(self::CF1, Member::RUOLO_VOLONTARIO, ['email' => 'keep@example.test']);
        $this->member(self::CF2, Member::RUOLO_DIPENDENTE);          // altra lista: mai rimosso da un file volontari
        $this->member(self::CF3, Member::RUOLO_ESTENSIONE);          // nel file non c'è → rimosso

        $svc = new S;
        $plan = $svc->plan($this->xlsx([
            [self::CF1, 'Anna', 'Volontario in Estensione', '0575 1, 333 2, 333 3'],
            ['DDDDDD00D00D000D', 'Dario', 'Volontario', ''],
        ]), 'volontari');
        $svc->apply($plan);

        $this->assertSame(1, $plan['counts'][S::OP_NUOVO]);
        $this->assertSame(1, $plan['counts'][S::OP_CAMBIO_RUOLO]);
        $this->assertSame(1, $plan['counts'][S::OP_RIMOSSO]);

        $a = Member::where('codice_fiscale', self::CF1)->first();
        $this->assertSame(Member::RUOLO_ESTENSIONE, $a->ruolo);
        $this->assertSame('keep@example.test', $a->email);            // dati esistenti mai sovrascritti
        $this->assertNull($a->telefono);

        $this->assertNull(Member::where('codice_fiscale', self::CF2)->first()->deleted_at);
        $c = Member::withTrashed()->where('codice_fiscale', self::CF3)->first();
        $this->assertNotNull($c->deleted_at);
        $this->assertTrue($c->disabilitato);

        $nuovo = Member::where('codice_fiscale', 'DDDDDD00D00D000D')->first();
        $this->assertSame('1986-07-09', $nuovo->data_nascita->toDateString());
    }

    public function test_telefoni_primo_principale_altri_aggiuntivi(): void
    {
        $svc = new S;
        $svc->apply($svc->plan($this->xlsx([[self::CF1, 'Anna', 'Dipendente', '0575 1, 333 2, 333 3']]), 'dipendenti'));

        $m = Member::first();
        $this->assertSame('0575 1', $m->telefono);
        $this->assertSame(['333 2', '333 3'], $m->telefoni_aggiuntivi);
    }

    public function test_ripristino_di_membro_rimosso_con_nuovo_stato(): void
    {
        $m = $this->member(self::CF1, Member::RUOLO_VOLONTARIO);
        $m->rimuovi();

        $svc = new S;
        $plan = $svc->plan($this->xlsx([[self::CF1, 'Anna', 'Dipendente', '']]), 'dipendenti');
        $svc->apply($plan);

        $m->refresh();
        $this->assertSame(1, $plan['counts'][S::OP_RIPRISTINO]);
        $this->assertNull($m->deleted_at);
        $this->assertFalse($m->disabilitato);
        $this->assertSame(Member::RUOLO_DIPENDENTE, $m->ruolo);
    }

    public function test_file_della_lista_sbagliata_blocca_tutto(): void
    {
        $this->member(self::CF2, Member::RUOLO_DIPENDENTE);

        $plan = (new S)->plan($this->xlsx([[self::CF1, 'Anna', 'Volontario', '']]), 'dipendenti');

        $this->assertNotNull($plan['blocking']);
        $this->assertSame([], $plan['ops']);
    }

    public function test_cf_non_valido_e_duplicato_sono_errori_non_applicati(): void
    {
        $svc = new S;
        $plan = $svc->plan($this->xlsx([
            ['CORTO', 'Anna', 'Dipendente', ''],
            [self::CF1, 'Bea', 'Dipendente', ''],
            [self::CF1, 'Bea', 'Dipendente', ''],
        ]), 'dipendenti');
        $svc->apply($plan);

        $this->assertSame(2, $plan['counts'][S::OP_ERRORE]);
        $this->assertSame(1, Member::count());
    }

    private function editor(array $permissions): \App\Models\Admin
    {
        return \App\Models\Admin::create([
            'name' => 'Ed', 'email' => 'ed@example.test', 'password' => 'x',
            'role' => 'editor', 'active' => true, 'permissions' => $permissions,
        ]);
    }

    public function test_senza_permesso_membri_403(): void
    {
        $this->actingAs($this->editor(['posts' => ['read', 'write']]), 'admin')
            ->get(route('admin.membri.index'))->assertForbidden();
    }

    public function test_file_anteprima_vecchi_o_bloccati_vengono_ripuliti(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        $disk->put('member-imports/vecchio.xlsx', 'x');
        touch($disk->path('member-imports/vecchio.xlsx'), now()->subHours(2)->getTimestamp());

        // file della lista sbagliata → import bloccato → anche il file appena caricato viene cancellato
        $file = new \Illuminate\Http\UploadedFile($this->xlsx([[self::CF1, 'Anna', 'Volontario', '']]), 'd.xlsx', null, null, true);
        $this->actingAs($this->editor(['membri' => ['read', 'write']]), 'admin')
            ->post(route('admin.membri.import.preview'), ['lista' => 'dipendenti', 'file' => $file])->assertOk();

        $this->assertSame([], $disk->files('member-imports'));
    }

    public function test_flusso_http_anteprima_e_conferma(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->member(self::CF3, Member::RUOLO_DIPENDENTE);
        $ed = $this->editor(['membri' => ['read', 'write']]);
        $file = new \Illuminate\Http\UploadedFile($this->xlsx([[self::CF1, 'Anna', 'Dipendente', '']]), 'd.xlsx', null, null, true);

        $res = $this->actingAs($ed, 'admin')->post(route('admin.membri.import.preview'), ['lista' => 'dipendenti', 'file' => $file]);
        $res->assertOk()->assertSee('Da rimuovere');
        $token = $res->viewData('token');

        $this->post(route('admin.membri.import.apply'), ['lista' => 'dipendenti', 'token' => $token])
            ->assertRedirect(route('admin.membri.index'));

        $this->assertNotNull(Member::where('codice_fiscale', self::CF1)->first());
        $this->assertNull(Member::where('codice_fiscale', self::CF3)->first());
        $this->get(route('admin.membri.index'))->assertOk()->assertSee('Rossi');
    }
}
