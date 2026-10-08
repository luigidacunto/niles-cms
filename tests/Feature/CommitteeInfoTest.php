<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CommitteeInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommitteeInfoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => [],
        ]);
    }

    public function test_footer_shows_nothing_when_no_data_set(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('tel:');
        $response->assertDontSee('mailto:');
    }

    public function test_payment_methods_default_then_editable_from_panel(): void
    {
        $this->assertSame(['Bonifico', 'Contanti'], CommitteeInfo::current()->metodi_pagamento);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.comitato.update'), ['metodi_pagamento_inviati' => 1, 'metodi_pagamento' => ['bonifico', 'pos', 'bonifico']])
            ->assertRedirect(route('admin.comitato.edit'));
        $this->assertSame(['Bonifico', 'POS'], CommitteeInfo::current()->metodi_pagamento);

        // Nessun metodo attivo non è ammesso; un codice sconosciuto neppure.
        $this->put(route('admin.comitato.update'), ['metodi_pagamento_inviati' => 1])->assertSessionHasErrors('metodi_pagamento');
        $this->put(route('admin.comitato.update'), ['metodi_pagamento_inviati' => 1, 'metodi_pagamento' => ['carta-di-credito-online']])->assertSessionHasErrors('metodi_pagamento.0');
        $this->assertSame(['Bonifico', 'POS'], CommitteeInfo::current()->metodi_pagamento);

        // Aggiornamento parziale senza il campo dei metodi: restano com'erano.
        $this->put(route('admin.comitato.update'), ['denominazione' => 'X'])->assertSessionDoesntHaveErrors();
        $this->assertSame(['Bonifico', 'POS'], CommitteeInfo::current()->metodi_pagamento);

        // Vecchio formato (elenco di etichette libere): riconosciute quelle del catalogo, ignorate le altre.
        CommitteeInfo::current()->forceFill(['metodi_pagamento' => ['bonifico', 'Pos', 'Assegno circolare speciale']])->save();
        $this->assertSame(['Bonifico', 'POS'], CommitteeInfo::current()->metodi_pagamento);

        // Colonna vuota/illeggibile: valgono i predefiniti del catalogo.
        CommitteeInfo::current()->forceFill(['metodi_pagamento' => []])->save();
        $this->assertSame(['Bonifico', 'Contanti'], CommitteeInfo::current()->metodi_pagamento);
    }

    public function test_nav_falls_back_to_site_name_without_a_logo(): void
    {
        $this->get('/')->assertSee(config('app.public_name'));
    }

    public function test_admin_can_upload_and_remove_logo(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.comitato.update'), [
                'logo_orizzontale' => UploadedFile::fake()->image('logo.png', 600, 140),
            ])
            ->assertRedirect(route('admin.comitato.edit'));

        $path = CommitteeInfo::current()->logo_orizzontale;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.comitato.update'), ['remove_logo_orizzontale' => '1']);

        $this->assertNull(CommitteeInfo::current()->logo_orizzontale);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_partner_logos_fall_back_to_text_pills_without_files(): void
    {
        $this->get('/')
            ->assertSee('ifrc.org')
            ->assertSee('cri.it')
            ->assertDontSee('<img src="/storage/committee/', false);
    }

    public function test_admin_can_upload_and_remove_partner_logos(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.comitato.update'), [
                'logo_ifrc' => UploadedFile::fake()->image('ifrc.png', 300, 100),
                'logo_un_italia' => UploadedFile::fake()->image('uia.png', 300, 100),
            ])
            ->assertRedirect(route('admin.comitato.edit'));

        $info = CommitteeInfo::current();
        Storage::disk('public')->assertExists($info->logo_ifrc);
        Storage::disk('public')->assertExists($info->logo_un_italia);

        $this->get('/')
            ->assertSee($info->logo_ifrc_url, false)
            ->assertSee($info->logo_un_italia_url, false)
            ->assertDontSee('>ifrc.org<', false);

        $path = $info->logo_ifrc;
        $this->actingAs($admin, 'admin')
            ->put(route('admin.comitato.update'), ['remove_logo_ifrc' => '1']);

        $this->assertNull(CommitteeInfo::current()->logo_ifrc);
        Storage::disk('public')->assertMissing($path);
        $this->get('/')->assertSee('>ifrc.org<', false);
    }

    public function test_custom_favicon_replaces_the_generic_one(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->get('/')->assertSee('favicons/favicon.ico', false);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.comitato.update'), ['favicon' => UploadedFile::fake()->image('f.png', 128, 128)])
            ->assertRedirect(route('admin.comitato.edit'));

        $info = CommitteeInfo::current();
        $this->assertNotNull($info->favicon);
        $this->get('/')->assertSee($info->favicon_url, false)->assertDontSee('favicons/favicon.ico', false);

        $this->actingAs($admin, 'admin')->put(route('admin.comitato.update'), ['remove_favicon' => '1']);
        $this->assertNull(CommitteeInfo::current()->favicon);
    }

    public function test_footer_credits_niles_with_current_version(): void
    {
        $this->get('/')
            ->assertSee('Realizzato con', false)
            ->assertSee('https://github.com/luigidacunto/niles-cms', false)
            ->assertSee('v'.config('app.version'), false);
    }

    public function test_footer_shows_contact_data_once_set(): void
    {
        CommitteeInfo::current()->update([
            'telefono' => '0575 24 398',
            'email' => 'segreteria@comitato.test',
            'indirizzo' => 'Viale Raffaello Sanzio, snc',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('0575 24 398');
        $response->assertSee('segreteria@comitato.test');
        $response->assertSee('Viale Raffaello Sanzio, snc');
    }

    public function test_only_admin_role_can_update_committee_info(): void
    {
        $editor = $this->admin('editor');

        $this->actingAs($editor, 'admin')
            ->put(route('admin.comitato.update'), ['denominazione' => 'Hack'])
            ->assertForbidden();
    }

    public function test_admin_can_update_committee_info(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.comitato.update'), [
                'denominazione' => 'Croce Rossa Italiana - Comitato di Esempio',
                'piva' => '02174760518',
            ])
            ->assertRedirect(route('admin.comitato.edit'));

        $this->assertSame('02174760518', CommitteeInfo::current()->piva);
    }
}
