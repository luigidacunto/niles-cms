<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'segreta-123',
            'role' => 'admin', 'active' => true, 'permissions' => [], 'password_login_enabled' => true,
        ]);
    }

    public function test_without_demo_flag_writes_work(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.categories.store'), ['name' => 'Prova'])
            ->assertSessionMissing('status', 'Versione dimostrativa: le modifiche non vengono salvate.');
    }

    public function test_demo_blocks_admin_writes(): void
    {
        config(['app.demo' => true]);

        $this->actingAs($this->admin(), 'admin')
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => 'Prova'])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHas('status', 'Versione dimostrativa: le modifiche non vengono salvate.');

        $this->assertSame(0, Category::count());
    }

    public function test_demo_blocks_public_forms(): void
    {
        config(['app.demo' => true]);

        $this->post('/preferenze/'.str_repeat('a', 64).'/cancellazione')
            ->assertSessionHasErrors('demo');
    }

    public function test_demo_blocks_area_soci_login(): void
    {
        config(['app.demo' => true]);

        $this->post(route('soci.login.send'), ['email' => 'a@example.test'])
            ->assertSessionHasErrors('demo');
    }

    public function test_demo_keeps_login_and_logout_working(): void
    {
        config(['app.demo' => true]);
        $this->admin();

        $this->post(route('admin.login.password.confirm'), ['email' => 'admin@example.test', 'password' => 'segreta-123'])
            ->assertRedirect();
        $this->assertAuthenticated('admin');

        $this->post(route('admin.logout'))->assertRedirect();
        $this->assertGuest('admin');
    }

    public function test_demo_shows_banners_and_credentials(): void
    {
        config(['app.demo' => true, 'app.demo_accounts' => ['Editor: editor@example.test / prova']]);

        $this->get('/')->assertSee('Versione dimostrativa di NILES');
        $this->get(route('admin.login.password'))
            ->assertSee('Versione dimostrativa')
            ->assertSee('Editor: editor@example.test / prova');
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertSee('le modifiche non vengono salvate');
    }
}
