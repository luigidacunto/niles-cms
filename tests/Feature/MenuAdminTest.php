<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Menu laterale del pannello: titoli fissi, gruppi richiudibili (sottomenu AdminLTE) e file della memoria dei gruppi e della ricerca. */
class MenuAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role, array $permessi = []): Admin
    {
        return Admin::create(['name' => ucfirst($role), 'email' => $role.rand(1, 9999).'@example.test', 'password' => 'x', 'role' => $role, 'active' => true, 'permissions' => $permessi]);
    }

    public function test_titoli_fissi_e_gruppi_richiudibili_nell_ordine_previsto(): void
    {
        $html = $this->actingAs($this->admin('admin'), 'admin')->get(route('admin.persone.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/nav-header[^>]*>\s*Lavoro quotidiano.*>\s*Contenuti.*>\s*Formazione.*>\s*Corsi\s*<.*>\s*Persone\s*<.*>\s*Tipologie corso\s*<'
            .'.*nav-header[^>]*>\s*Amministrazione.*>\s*Comitato e sito.*>\s*Configurazione.*>\s*Classificazioni.*>\s*Utenti e controllo/s',
            $html
        );
        // I gruppi sono sottomenu di AdminLTE (richiudibili): le pagine stanno in un nav-treeview.
        $this->assertMatchesRegularExpression('/nav-treeview.*admin\/corsi-catalogo\/tipologie/s', $html);
        // Il gruppo con la pagina corrente è aperto (classe menu-open, messa da AdminLTE).
        $this->assertMatchesRegularExpression('/menu-open[^>]*>\s*<a[^>]*>\s*<i[^>]*fa-graduation-cap/s', $html);
    }

    public function test_editor_con_corsi_vede_formazione_ma_non_amministrazione_ne_tipologie(): void
    {
        // Il titolo `nav-header` esiste solo nella barra laterale ("Formazione" altrove è un nome di categoria).
        $editor = $this->actingAs($this->admin('editor', ['corsi' => ['read']]), 'admin')->get(route('admin.persone.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/href="[^"]*\/admin\/corsi"/', $editor);
        $this->assertDoesNotMatchRegularExpression('/admin\/corsi-catalogo\/tipologie/', $editor);   // Tipologie corso: solo admin
        $this->assertDoesNotMatchRegularExpression('/nav-header[^>]*>\s*Amministrazione/', $editor);
        $this->assertDoesNotMatchRegularExpression('/>\s*Comitato e sito/', $editor);
    }

    public function test_senza_permesso_corsi_il_gruppo_formazione_non_compare(): void
    {
        // Test separato: il menu di AdminLTE è costruito una volta per istanza dell'applicazione, nelle richieste vere no.
        $senza = $this->actingAs($this->admin('editor', ['posts' => ['read']]), 'admin')->get(route('admin.posts.index'))->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/fa-graduation-cap/', $senza);   // gruppo vuoto: AdminLTE lo scarta
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

    public function test_footer_del_pannello_con_versione_e_link_al_progetto(): void
    {
        $this->actingAs($this->admin('admin'), 'admin')->get(route('admin.dashboard'))->assertOk()
            ->assertSee('main-footer', false)
            ->assertSee('<a href="https://github.com/luigidacunto/niles-cms"', false)   // HTML vero, non testo con l'escape
            ->assertDontSee('&lt;a href=&quot;https://github.com', false)
            ->assertSee('v'.config('app.version'))
            ->assertSee('layout-fixed', false);   // barra laterale fissa
    }
}
