<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GateStagingToAdminsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'x',
            'role' => 'admin', 'active' => true, 'permissions' => [],
        ]);
    }

    public function test_no_op_when_flag_disabled(): void
    {
        config(['staging.gate_public' => false]);

        $this->get('/')->assertOk()->assertDontSee('Sito in fase di verifica');
    }

    public function test_shows_courtesy_page_to_anonymous_visitors_when_enabled(): void
    {
        config(['staging.gate_public' => true]);

        $this->get('/')->assertOk()->assertSee('Sito in fase di verifica');
        // Qualsiasi URL, non solo la home.
        $this->get('/una-pagina-qualsiasi-inesistente')->assertOk()->assertSee('Sito in fase di verifica');
    }

    public function test_shows_real_site_to_logged_in_admin_when_enabled(): void
    {
        config(['staging.gate_public' => true]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->get('/')
            ->assertOk()
            ->assertDontSee('Sito in fase di verifica');
    }

    public function test_admin_login_route_stays_reachable_when_enabled(): void
    {
        config(['staging.gate_public' => true]);

        $this->get(route('admin.login'))->assertOk()->assertDontSee('Sito in fase di verifica');
    }

    public function test_never_active_in_production_even_if_flag_left_true(): void
    {
        config(['staging.gate_public' => true]);
        app()->detectEnvironment(fn () => 'production');

        $this->get('/')->assertOk()->assertDontSee('Sito in fase di verifica');
    }
}
