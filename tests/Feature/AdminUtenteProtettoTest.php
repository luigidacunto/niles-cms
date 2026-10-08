<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Utente protetto (primo admin): nome, email, password e login con password si modificano; ruolo e categorie mai. */
class AdminUtenteProtettoTest extends TestCase
{
    use RefreshDatabase;

    private function protetto(): Admin
    {
        $admin = Admin::create(['name' => 'Administrator', 'email' => 'administrator@example.test', 'password' => 'una-password-lunga', 'role' => 'admin',
            'active' => true, 'password_login_enabled' => true, 'all_categories' => true]);
        $admin->forceFill(['protected' => true])->save();

        return $admin;
    }

    public function test_si_puo_disabilitare_il_login_con_password_senza_inviare_ruolo(): void
    {
        $protetto = $this->protetto();

        // Il modulo, per l'utente protetto, ha ruolo e categorie disabilitati: il browser non li invia.
        $this->actingAs($protetto, 'admin')->put(route('admin.users.update', $protetto), [
            'name' => 'Administrator CRI', 'email' => 'administrator@example.test', 'active' => 1,
            // niente 'role', niente 'all_categories', 'password_login_enabled' assente = spento
        ])->assertSessionDoesntHaveErrors()->assertRedirect(route('admin.users.index'));

        $protetto->refresh();
        $this->assertSame('Administrator CRI', $protetto->name);
        $this->assertFalse($protetto->password_login_enabled);
        $this->assertSame('admin', $protetto->role);
        $this->assertTrue($protetto->all_categories);     // non azzerato dal campo mancante
        $this->assertTrue($protetto->protected);
    }

    public function test_ruolo_inviato_a_forza_per_l_utente_protetto_viene_ignorato(): void
    {
        $protetto = $this->protetto();
        $altro = Admin::create(['name' => 'Altro', 'email' => 'altro@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);

        $this->actingAs($altro, 'admin')->put(route('admin.users.update', $protetto), [
            'name' => 'Administrator', 'email' => 'administrator@example.test', 'active' => 1, 'role' => 'editor', 'all_categories' => 0,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('admin', $protetto->fresh()->role);
        $this->assertTrue($protetto->fresh()->all_categories);
    }

    public function test_un_utente_normale_continua_a_richiedere_il_ruolo(): void
    {
        $admin = Admin::create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'x', 'role' => 'admin', 'active' => true]);
        $editor = Admin::create(['name' => 'Ed', 'email' => 'ed@example.test', 'password' => 'x', 'role' => 'editor', 'active' => true]);

        $this->actingAs($admin, 'admin')->put(route('admin.users.update', $editor), [
            'name' => 'Ed', 'email' => 'ed@example.test', 'active' => 1,
        ])->assertSessionHasErrors('role');
    }
}
