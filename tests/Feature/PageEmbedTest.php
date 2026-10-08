<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageEmbedTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', array $permissions = []): Admin
    {
        static $n = 0;

        return Admin::create([
            'name' => 'Tester', 'email' => $role.(++$n).'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => $permissions,
        ]);
    }

    private function page(array $attrs = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'donazioni', 'title' => 'Donazioni', 'body' => '<p>intro</p>',
            'published' => true, 'order' => 0,
        ], $attrs));
    }

    private const SNIPPET = '<script src="https://example.com/donate.js"></script><form action="x"></form>';

    public function test_admin_can_set_embed_html_and_it_renders_raw(): void
    {
        $page = $this->page(['system' => true]);

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/pagine/'.$page->id, [
                'title' => 'Donazioni', 'body' => '<p>intro</p>', 'published' => '1',
                'embed_html' => self::SNIPPET,
            ])
            ->assertRedirect('/admin/pagine');

        $this->assertSame(self::SNIPPET, $page->refresh()->embed_html);

        // Reso grezzo sulla pagina pubblica (non sanitizzato, non escapato).
        $this->get('/donazioni')->assertOk()->assertSee(self::SNIPPET, false);
    }

    public function test_body_stays_sanitized_even_with_an_embed(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin(), 'admin')->put('/admin/pagine/'.$page->id, [
            'title' => 'Donazioni', 'published' => '1',
            'body' => '<p>ok</p><script>alert(1)</script>',
            'embed_html' => self::SNIPPET,
        ]);

        $this->assertStringNotContainsString('<script>alert', $page->refresh()->body);
        $this->assertStringContainsString('<script', $page->embed_html);
    }

    public function test_editor_cannot_set_embed_html(): void
    {
        $page = $this->page();
        $editor = $this->admin('editor', ['pages' => ['read', 'write']]);

        $this->actingAs($editor, 'admin')->put('/admin/pagine/'.$page->id, [
            'title' => 'Donazioni', 'body' => '<p>intro</p>', 'published' => '1',
            'embed_html' => self::SNIPPET,
        ])->assertRedirect('/admin/pagine');

        $this->assertNull($page->refresh()->embed_html);

        // Il campo non è nemmeno nel form per un editor.
        $this->actingAs($editor, 'admin')->get('/admin/pagine/'.$page->id.'/modifica')
            ->assertOk()->assertDontSee('Codice embed');
    }
}
