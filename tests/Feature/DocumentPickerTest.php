<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentPickerTest extends TestCase
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

    public function test_picker_returns_only_published_library_documents(): void
    {
        $lib = DocumentCategory::create(['name' => 'Moduli', 'slug' => 'moduli', 'scope' => 'library']);
        $tra = DocumentCategory::create(['name' => 'Bilanci', 'slug' => 'bilanci', 'scope' => 'trasparenza']);

        Document::create(['document_category_id' => $lib->id, 'title' => 'Modulo pubblico', 'file_path' => 'documenti/a.pdf', 'published' => true]);
        Document::create(['document_category_id' => $lib->id, 'title' => 'Modulo bozza', 'file_path' => 'documenti/b.pdf', 'published' => false]);
        Document::create(['document_category_id' => null, 'title' => 'Senza categoria', 'file_path' => 'documenti/c.docx', 'published' => true]);
        Document::create(['document_category_id' => $tra->id, 'title' => 'Bilancio trasparenza', 'file_path' => 'documenti/d.pdf', 'published' => true]);

        $res = $this->actingAs($this->admin(), 'admin')->getJson('/admin/documenti/picker');

        $senzaCat = Document::where('title', 'Senza categoria')->first();

        $res->assertOk()
            ->assertJsonFragment(['title' => 'Modulo pubblico'])
            ->assertJsonFragment(['title' => 'Senza categoria', 'url' => route('documenti.show', $senzaCat), 'ext' => 'docx'])
            ->assertJsonMissing(['title' => 'Modulo bozza'])
            ->assertJsonMissing(['title' => 'Bilancio trasparenza']);
    }

    public function test_picker_search_filters_by_title(): void
    {
        $lib = DocumentCategory::create(['name' => 'Moduli', 'slug' => 'moduli', 'scope' => 'library']);
        Document::create(['document_category_id' => $lib->id, 'title' => 'Regolamento gare', 'file_path' => 'documenti/a.pdf', 'published' => true]);
        Document::create(['document_category_id' => $lib->id, 'title' => 'Volantino corso', 'file_path' => 'documenti/b.pdf', 'published' => true]);

        $this->actingAs($this->admin(), 'admin')->getJson('/admin/documenti/picker?q=volant')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Volantino corso']);
    }

    public function test_editor_with_posts_write_can_use_picker_without_documents_permission(): void
    {
        $editor = $this->admin('editor', ['posts' => ['read', 'write']]);
        $this->actingAs($editor, 'admin')->getJson('/admin/documenti/picker')->assertOk();

        $noAccess = $this->admin('editor', ['pages' => ['read']]);
        $this->actingAs($noAccess, 'admin')->getJson('/admin/documenti/picker')->assertForbidden();
    }

    public function test_post_and_page_forms_include_the_picker(): void
    {
        $admin = $this->admin();
        Category::create(['name' => 'News', 'slug' => 'news', 'order' => 0]);

        $this->actingAs($admin, 'admin')->get('/admin/post/nuovo')
            ->assertOk()->assertSee('documentPickerModal')->assertSee('insertDocument');
        $this->actingAs($admin, 'admin')->get('/admin/pagine/nuova')
            ->assertOk()->assertSee('documentPickerModal')->assertSee('insertDocument');
    }
}
