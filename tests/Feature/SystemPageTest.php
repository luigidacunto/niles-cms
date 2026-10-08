<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemPageTest extends TestCase
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

    private function systemPage(array $attrs = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'trasparenza', 'title' => 'Trasparenza', 'body' => '<p>intro</p>',
            'published' => true, 'in_menu' => false, 'order' => 0, 'system' => true,
        ], $attrs));
    }

    public function test_system_page_cannot_be_deleted(): void
    {
        $page = $this->systemPage();

        $this->actingAs($this->admin(), 'admin')
            ->delete('/admin/pagine/'.$page->id)
            ->assertForbidden();

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    public function test_system_page_structure_fields_are_ignored_on_update(): void
    {
        $parent = Page::create(['slug' => 'chi-siamo', 'title' => 'Chi Siamo', 'published' => true, 'order' => 0]);
        $page = $this->systemPage();

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/pagine/'.$page->id, [
                'title' => 'Trasparenza aggiornata',
                'excerpt' => 'nuovo estratto',
                'body' => '<p>nuovo testo</p>',
                'slug' => 'trasparenza-hackerata',
                'parent_id' => $parent->id,
                'order' => 99,
                'in_menu' => '1',
                'template' => 'qualcosa',
                'published' => '1',
            ])
            ->assertRedirect('/admin/pagine');

        $page->refresh();
        // Testo introduttivo: aggiornato.
        $this->assertSame('Trasparenza aggiornata', $page->title);
        $this->assertSame('nuovo estratto', $page->excerpt);
        // Struttura: invariata.
        $this->assertSame('trasparenza', $page->slug);
        $this->assertNull($page->parent_id);
        $this->assertFalse($page->in_menu);
        $this->assertNull($page->template);
        // L'ordine però È modificabile (posizione nel menu, non struttura) — 99 tra 1 fratello → posizione 1.
        $this->assertSame(1, $page->order);
    }

    public function test_page_order_change_reshuffles_siblings(): void
    {
        $parent = Page::create(['slug' => 'gruppo', 'title' => 'Gruppo', 'published' => true, 'order' => 0]);
        $a = Page::create(['slug' => 'a', 'title' => 'A', 'published' => true, 'order' => 0, 'parent_id' => $parent->id]);
        $b = Page::create(['slug' => 'b', 'title' => 'B', 'published' => true, 'order' => 1, 'parent_id' => $parent->id]);
        $c = Page::create(['slug' => 'c', 'title' => 'C', 'published' => true, 'order' => 2, 'parent_id' => $parent->id]);

        // Sposto C in posizione 0 → A e B scalano
        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/pagine/'.$c->id, [
                'title' => 'C', 'body' => '<p>x</p>', 'order' => 0, 'published' => '1',
                'parent_id' => $parent->id, 'in_menu' => '1',
            ])
            ->assertRedirect('/admin/pagine');

        $this->assertSame(0, $c->refresh()->order);
        $this->assertSame(1, $a->refresh()->order);
        $this->assertSame(2, $b->refresh()->order);
    }

    public function test_unpublished_system_page_drops_from_menu_and_public_route(): void
    {
        $page = $this->systemPage(['in_menu' => true, 'parent_id' => null]);

        // Pubblicata → nel menuTree
        $this->assertTrue(Page::menuTree()->contains('id', $page->id));

        $page->update(['published' => false]);

        $this->assertFalse(Page::menuTree()->contains('id', $page->id));
        // La pagina pubblica /{slug} dà 404 (published=false)
        $this->get('/trasparenza-non-esiste')->assertNotFound();
    }

    public function test_system_page_edit_form_shows_the_banner_and_locks_structure(): void
    {
        $page = $this->systemPage();

        $this->actingAs($this->admin(), 'admin')->get('/admin/pagine/'.$page->id.'/modifica')
            ->assertOk()
            ->assertSee('Sezione di sistema')
            ->assertSee('Sezione attiva')
            ->assertDontSee('Struttura del sito');
    }

    public function test_non_system_page_still_deletable(): void
    {
        $page = Page::create(['slug' => 'normale', 'title' => 'Normale', 'published' => true, 'order' => 0]);

        $this->actingAs($this->admin(), 'admin')
            ->delete('/admin/pagine/'.$page->id)
            ->assertRedirect('/admin/pagine');

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
