<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu laterale del pannello: sezione Formazione dedicata e file del menu collassabile con ricerca. */
class MenuAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role, array $permessi = []): Admin
    {
        return Admin::create(['name' => ucfirst($role), 'email' => $role.rand(1, 9999).'@example.test', 'password' => 'x', 'role' => $role, 'active' => true, 'permissions' => $permessi]);
    }

    public function test_corsi_persone_e_tipologie_stanno_nella_sezione_formazione(): void
    {
        $html = $this->actingAs($this->admin('admin'), 'admin')->get(route('admin.persone.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/nav-header[^>]*>\s*Contenuti.*nav-header[^>]*>\s*Formazione.*>\s*Corsi\s*<.*>\s*Persone\s*<.*>\s*Tipologie corso\s*<.*nav-header[^>]*>\s*Organizzazione/s',
            $html
        );

        // Fuori da "Contenuti": tra le intestazioni Contenuti e Formazione non ci sono più Corsi, Persone, Tipologie corso.
        preg_match('/nav-header[^>]*>\s*Contenuti(.*?)nav-header[^>]*>\s*Formazione/s', $html, $m);
        foreach (['Corsi', 'Persone', 'Tipologie corso'] as $voce) {
            $this->assertDoesNotMatchRegularExpression('/>\s*'.$voce.'\s*</', $m[1], "{$voce} è ancora sotto Contenuti");
        }
    }

    public function test_formazione_visibile_con_permesso_corsi_e_tipologie_solo_agli_admin(): void
    {
        // L'intestazione di sezione `nav-header` esiste solo nella barra laterale ("Formazione" altrove è un nome di categoria).
        $editor = $this->actingAs($this->admin('editor', ['corsi' => ['read']]), 'admin')->get(route('admin.persone.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/nav-header[^>]*>\s*Formazione/', $editor);
        $this->assertMatchesRegularExpression('/href="[^"]*\/admin\/corsi"/', $editor);
        $this->assertDoesNotMatchRegularExpression('/admin\/corsi-catalogo\/tipologie/', $editor);   // Tipologie corso: solo admin
    }

    public function test_senza_permesso_corsi_la_sezione_formazione_non_compare(): void
    {
        // Test separato: il menu di AdminLTE è costruito una volta per istanza dell'applicazione, nelle richieste vere no.
        $senza = $this->actingAs($this->admin('editor', ['posts' => ['read']]), 'admin')->get(route('admin.posts.index'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/nav-header[^>]*>\s*Formazione/', $senza);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\/admin\/(corsi|persone)"/', $senza);
    }

    public function test_menu_collassabile_con_ricerca_carica_i_suoi_file(): void
    {
        $this->actingAs($this->admin('admin'), 'admin')->get(route('admin.persone.index'))->assertOk()
            ->assertSee('css/admin-menu.css?v='.config('app.version'), false)
            ->assertSee('js/admin-menu.js?v='.config('app.version'), false);

        $this->assertFileExists(public_path('js/admin-menu.js'));
        $this->assertFileExists(public_path('css/admin-menu.css'));
    }
}
