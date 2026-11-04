<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Permesso unico (lettura+scrittura) su tutte le sezioni tranne «corsi», che resta separato. */
class PermessiUnificatiTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_migrazione_unifica_la_sola_lettura_e_lascia_i_corsi(): void
    {
        $editor = Admin::create(['name' => 'E', 'email' => 'e@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true,
            'permissions' => ['posts' => ['read'], 'pages' => ['read', 'write'], 'membri' => ['write'], 'corsi' => ['read']]]);
        $senza = Admin::create(['name' => 'S', 'email' => 's@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true, 'permissions' => []]);

        (require database_path('migrations/2026_11_04_100000_unifica_permessi_lettura_scrittura.php'))->up();

        $this->assertSame(['read', 'write'], $editor->fresh()->permissions['posts']);
        $this->assertSame(['read', 'write'], $editor->fresh()->permissions['pages']);
        $this->assertSame(['read', 'write'], $editor->fresh()->permissions['membri']);
        $this->assertSame(['read'], $editor->fresh()->permissions['corsi']);           // separato: invariato
        $this->assertArrayNotHasKey('images', $editor->fresh()->permissions);          // chi non aveva nulla non guadagna nulla
        $this->assertSame([], $senza->fresh()->permissions);
    }

    public function test_il_form_salva_permesso_unico_e_corsi_separato(): void
    {
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);

        $crea = fn (string $email, array $permessi) => $this->actingAs($admin, 'admin')->post(route('admin.users.store'), [
            'name' => 'Nuovo', 'email' => $email, 'role' => 'editor', 'all_categories' => 1, 'permissions' => $permessi,
        ]);

        $crea('uno@example.test', ['posts' => ['read'], 'corsi' => ['read']])->assertSessionHasNoErrors();
        $uno = Admin::firstWhere('email', 'uno@example.test');
        $this->assertSame(['read', 'write'], $uno->permissions['posts']);              // la sola «lettura» diventa lettura+scrittura
        $this->assertSame(['read'], $uno->permissions['corsi']);                       // corsi: lettura da sola resta tale

        $crea('due@example.test', ['corsi' => ['write']]);
        $this->assertSame(['read', 'write'], Admin::firstWhere('email', 'due@example.test')->permissions['corsi']);   // scrittura ⇒ lettura

        $this->assertSame(['posts', 'pages', 'trasparenza', 'documents', 'images', 'files', 'comunicazioni_soci', 'membri', 'corsi'], array_keys(config('permissions')));
    }

    public function test_la_griglia_mostra_corsi_per_ultimo(): void
    {
        $admin = Admin::create(['name' => 'A', 'email' => 'a@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.users.create'))->assertOk()
            ->assertSeeInOrder(['perm-posts', 'perm-membri', 'perm-corsi-read', 'perm-corsi-write']);
    }
}
