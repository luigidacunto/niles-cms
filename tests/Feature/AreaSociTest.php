<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\ComunicazioneSoci;
use App\Models\Document;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AreaSociTest extends TestCase
{
    use RefreshDatabase;

    private function attiva(bool $on = true): void
    {
        CommitteeInfo::current()->update(['area_soci_attiva' => $on]);
    }

    private function member(): Member
    {
        return Member::create([
            'codice_fiscale' => 'AAAAAA00A00A000A', 'nome' => 'Anna', 'cognome' => 'Rossi',
            'ruolo' => Member::RUOLO_VOLONTARIO, 'email' => 'anna@example.test',
        ]);
    }

    private function admin(string $role = 'admin', array $permissions = []): Admin
    {
        return Admin::create(['name' => 'Ada', 'email' => $role.'@example.test', 'password' => 'x', 'role' => $role, 'active' => true, 'permissions' => $permissions]);
    }

    private function comunicazione(array $extra = []): ComunicazioneSoci
    {
        return ComunicazioneSoci::create($extra + ['slug' => 'avviso', 'title' => 'Avviso importante', 'body' => '<p>Testo</p>', 'published' => true, 'published_at' => now()->subMinute()]);
    }

    public function test_spenta_di_default_tutto_nascosto_e_nulla_cancellato(): void
    {
        $this->assertFalse(CommitteeInfo::current()->area_soci_attiva);
        $this->comunicazione();
        $this->member();

        $this->get(route('soci.login'))->assertNotFound();
        $this->get(route('home'))->assertDontSee(route('soci.login'), false);
        $this->actingAs($this->admin(), 'admin')->get(route('admin.membri.index'))->assertNotFound();
        $this->get(route('admin.comunicazioni-soci.index'))->assertNotFound();

        $this->assertSame(1, ComunicazioneSoci::count());
        $this->assertSame(1, Member::count());

        $this->attiva();
        $this->get(route('soci.login'))->assertOk();
        $this->assertSame(1, ComunicazioneSoci::count());
    }

    public function test_spegnere_l_area_scollega_i_soci(): void
    {
        $this->attiva();
        $this->actingAs($this->member(), 'member')->get(route('soci.profilo'))->assertOk();

        $this->attiva(false);
        $this->get(route('home'))->assertOk(); // niente errori sul sito pubblico
        $this->assertGuest('member');
    }

    public function test_menu_area_soci_solo_per_socio_loggato_e_comunicazioni_protette(): void
    {
        $this->attiva();
        $this->comunicazione();

        $this->get(route('home'))->assertDontSee('Area Soci');
        $this->get(route('soci.comunicazioni'))->assertRedirect(route('soci.login'));

        $this->actingAs($this->member(), 'member');
        $this->get(route('home'))->assertSee('Area Soci')->assertSee(route('soci.comunicazioni'), false);
        $this->get(route('soci.comunicazioni'))->assertOk()->assertSee('Avviso importante');
        $this->get(route('soci.comunicazioni.show', 'avviso'))->assertOk()->assertSee('Testo');
    }

    public function test_bozze_e_future_non_visibili(): void
    {
        $this->attiva();
        $this->comunicazione(['slug' => 'bozza', 'title' => 'Bozza', 'published' => false]);
        $this->comunicazione(['slug' => 'futura', 'title' => 'Futura', 'published_at' => now()->addDay()]);

        $this->actingAs($this->member(), 'member');
        $this->get(route('soci.comunicazioni'))->assertDontSee('Bozza')->assertDontSee('Futura');
        $this->get(route('soci.comunicazioni.show', 'bozza'))->assertNotFound();
        $this->get(route('soci.comunicazioni.show', 'futura'))->assertNotFound();
    }

    public function test_permesso_e_allegati_privati(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->attiva();

        $this->actingAs($this->admin('editor', ['posts' => ['read', 'write']]), 'admin')
            ->get(route('admin.comunicazioni-soci.index'))->assertForbidden();

        $ed = Admin::create(['name' => 'Ed', 'email' => 'ed@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true,
            'permissions' => ['comunicazioni_soci' => ['read', 'write']]]);
        $this->actingAs($ed, 'admin')->post(route('admin.comunicazioni-soci.store'), [
            'title' => 'Con allegato', 'body' => '<p>x</p>', 'published' => '1',
            'allegati' => [UploadedFile::fake()->create('verbale.pdf', 50, 'application/pdf')],
        ])->assertRedirect(route('admin.comunicazioni-soci.index'));

        $item = ComunicazioneSoci::first();
        $doc = $item->attachments()->first();
        $this->assertTrue($doc->isRiservato());
        Storage::disk('local')->assertExists($doc->file_path);
        Storage::disk('public')->assertMissing($doc->file_path); // mai sul disco pubblico

        // Non compare nella libreria pubblica né nei selettori.
        $this->actingAs($this->admin(), 'admin')->get(route('admin.documents.index'))->assertDontSee('verbale');

        // Download: ospite → login; socio → file; area spenta → 404.
        auth('admin')->logout();
        $this->get($doc->download_url)->assertRedirect(route('soci.login'));
        $this->actingAs($this->member(), 'member')->get($doc->download_url)->assertOk();
        $this->attiva(false);
        $this->get($doc->download_url)->assertNotFound();

        // Eliminare la comunicazione cancella documento e file.
        $this->actingAs($ed, 'admin');
        $this->attiva();
        $this->delete(route('admin.comunicazioni-soci.destroy', $item))->assertRedirect();
        $this->assertNull(Document::find($doc->id));
        Storage::disk('local')->assertMissing($doc->file_path);
    }

    public function test_permesso_comunicazioni_si_salva_come_unico_switch(): void
    {
        $this->actingAs($this->admin(), 'admin')->post(route('admin.users.store'), [
            'name' => 'Nuovo', 'email' => 'nuovo@example.test', 'role' => 'editor', 'active' => '1',
            'permissions' => ['comunicazioni_soci' => ['write']],
        ]);

        $this->assertSame(['read', 'write'], Admin::where('email', 'nuovo@example.test')->first()->permissions['comunicazioni_soci']);
    }
}
