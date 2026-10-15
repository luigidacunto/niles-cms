<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AggiornamentoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): Admin
    {
        return Admin::create([
            'name' => 'Mario Rossi', 'email' => $role.'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => [], 'all_categories' => true,
        ]);
    }

    private function attiva(): void
    {
        config(['app.web_update' => true, 'app.update_check.enabled' => false]);   // niente chiamate a GitHub nei test
    }

    public function test_senza_il_flag_la_pagina_e_i_passi_non_esistono(): void
    {
        config(['app.web_update' => false]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get('/admin/aggiornamento')->assertNotFound();
        $this->actingAs($admin, 'admin')->postJson('/admin/aggiornamento/passo', ['fase' => 'scarica'])->assertNotFound();
    }

    public function test_pagina_per_l_amministratore_con_il_flag(): void
    {
        $this->attiva();

        $this->actingAs($this->admin(), 'admin')->get('/admin/aggiornamento')
            ->assertOk()->assertSee('Aggiornamento di NILES')->assertSee('Copie del database');
    }

    public function test_un_editor_non_accede(): void
    {
        $this->attiva();

        $this->actingAs($this->admin('editor'), 'admin')->get('/admin/aggiornamento')->assertForbidden();
    }

    public function test_in_demo_e_disattivato_anche_con_il_flag(): void
    {
        $this->attiva();
        config(['app.demo' => true]);

        $this->actingAs($this->admin(), 'admin')->get('/admin/aggiornamento')->assertNotFound();
    }

    public function test_un_passo_sconosciuto_o_senza_aggiornamento_disponibile_da_errore_json(): void
    {
        $this->attiva();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->postJson('/admin/aggiornamento/passo', ['fase' => 'boh'])
            ->assertStatus(422)->assertJson(['ok' => false]);
        // Con il controllo versioni disattivato non c'è nessuna versione da scaricare.
        $this->actingAs($admin, 'admin')->postJson('/admin/aggiornamento/passo', ['fase' => 'scarica'])
            ->assertStatus(422)->assertJson(['ok' => false]);
        // Applicare senza aver preparato nulla non deve creare nessun token.
        $this->actingAs($admin, 'admin')->postJson('/admin/aggiornamento/passo', ['fase' => 'applica'])
            ->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertFileDoesNotExist(storage_path('app/private/update/token.json'));
    }

    public function test_copia_database_rifiuta_nomi_non_validi(): void
    {
        $this->attiva();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get('/admin/aggiornamento/copia/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin, 'admin')->get('/admin/aggiornamento/copia/niles-20260101-000000-abcdef12.sql')->assertNotFound();
    }

    public function test_il_pulsante_aggiorna_in_dashboard_c_e_solo_con_il_flag(): void
    {
        $admin = $this->admin();
        config(['app.update_check.enabled' => true, 'app.web_update' => false]);
        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()->assertDontSee('id="niles-aggiorna"', false);

        config(['app.web_update' => true]);
        $this->actingAs($admin, 'admin')->get('/admin')->assertOk()->assertSee('id="niles-aggiorna"', false);
    }
}
