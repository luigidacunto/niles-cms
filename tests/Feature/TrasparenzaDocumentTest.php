<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrasparenzaDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $attrs = []): DocumentCategory
    {
        return DocumentCategory::create(array_merge([
            'name' => 'Sovvenzioni', 'slug' => 'sovvenzioni', 'order' => 0, 'scope' => 'trasparenza',
        ], $attrs));
    }

    private function admin(string $role = 'admin', array $permissions = []): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => $role.'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => $permissions,
        ]);
    }

    public function test_trasparenza_page_lists_published_docs_and_hides_empty_categories(): void
    {
        Page::create(['slug' => 'trasparenza', 'title' => 'Trasparenza', 'body' => '<p>intro</p>', 'published' => true]);

        $full = $this->category();
        $empty = $this->category(['name' => 'Albo', 'slug' => 'albo', 'order' => 1]);

        Document::create([
            'document_category_id' => $full->id, 'title' => 'Doc pubblico',
            'type' => '5x1000', 'file_path' => 'documenti/a.pdf', 'published' => true,
            'published_at' => '2024-01-01',
        ]);
        Document::create([
            'document_category_id' => $full->id, 'title' => 'Doc nascosto',
            'file_path' => 'documenti/b.pdf', 'published' => false,
        ]);

        $this->get('/trasparenza')
            ->assertOk()
            ->assertSee('Doc pubblico')
            ->assertDontSee('Doc nascosto')
            ->assertSee('Sovvenzioni')
            ->assertDontSee('Albo');
    }

    public function test_library_only_category_is_not_shown_on_trasparenza(): void
    {
        Page::create(['slug' => 'trasparenza', 'title' => 'Trasparenza', 'published' => true]);
        $lib = $this->category(['name' => 'Organigramma', 'slug' => 'organigramma', 'scope' => 'library']);
        Document::create([
            'document_category_id' => $lib->id, 'title' => 'Organigramma 2025',
            'file_path' => 'documenti/o.pdf', 'published' => true, 'published_at' => '2025-01-01',
        ]);

        $this->get('/trasparenza')->assertOk()->assertDontSee('Organigramma 2025');
    }

    public function test_non_pdf_upload_is_rejected(): void
    {
        Storage::fake('public');
        $cat = $this->category();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/trasparenza/documenti', [
                'document_category_id' => $cat->id,
                'title' => 'X',
                'file' => UploadedFile::fake()->create('evil.php', 10, 'text/x-php'),
                'published' => '1',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_pdf_upload_creates_document_with_readable_disk_name(): void
    {
        Storage::fake('public');
        $cat = $this->category();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/trasparenza/documenti', [
                'document_category_id' => $cat->id,
                'title' => 'Bilancio consuntivo',
                'type' => 'Bilanci',
                'file' => UploadedFile::fake()->create('bilancio.pdf', 200, 'application/pdf'),
                'published' => '1',
            ])
            ->assertRedirect('/admin/trasparenza/documenti');

        $doc = Document::first();
        $this->assertSame('Bilancio consuntivo', $doc->title);
        $this->assertSame('bilancio.pdf', $doc->original_filename);
        $this->assertStringStartsWith('documenti/bilancio-consuntivo-', $doc->file_path);
        Storage::disk('public')->assertExists($doc->file_path);
    }

    public function test_area_with_documents_cannot_be_deleted(): void
    {
        $cat = $this->category();
        Document::create([
            'document_category_id' => $cat->id, 'title' => 'D', 'file_path' => 'documenti/x.pdf', 'published' => true,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->delete('/admin/trasparenza/aree/'.$cat->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('document_categories', ['id' => $cat->id]);
    }

    public function test_admin_form_views_render(): void
    {
        $cat = $this->category();
        $doc = Document::create([
            'document_category_id' => $cat->id, 'title' => 'D', 'file_path' => 'documenti/x.pdf',
            'type' => 'Bilanci', 'published' => true, 'published_at' => '2024-01-01',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get('/admin/trasparenza/documenti/nuovo')->assertOk()->assertSee('document-types');
        $this->actingAs($admin, 'admin')->get('/admin/trasparenza/documenti/'.$doc->id.'/modifica')->assertOk()->assertSee('Bilanci');
        $this->actingAs($admin, 'admin')->get('/admin/trasparenza/documenti/'.$doc->id)->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/trasparenza/aree')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/trasparenza/aree/'.$cat->id.'/modifica')->assertOk();
    }

    public function test_document_is_served_with_a_readable_filename(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documenti/abc123random.pdf', '%PDF-1.4 fake');
        $cat = $this->category();

        $pub = Document::create([
            'document_category_id' => $cat->id, 'title' => 'Bilancio 2024/2025',
            'file_path' => 'documenti/abc123random.pdf', 'published' => true,
        ]);
        $draft = Document::create([
            'document_category_id' => $cat->id, 'title' => 'Bozza',
            'file_path' => 'documenti/abc123random.pdf', 'published' => false,
        ]);

        $res = $this->get('/documenti/'.$pub->id);
        $res->assertOk();
        $this->assertStringContainsString('Bilancio 2024-2025.pdf', $res->headers->get('content-disposition'));
        $this->assertStringContainsString('inline', $res->headers->get('content-disposition'));

        $this->get('/documenti/'.$draft->id)->assertNotFound();
        $this->actingAs($this->admin(), 'admin')->get('/documenti/'.$draft->id)->assertOk();
    }

    public function test_editor_without_permission_is_denied(): void
    {
        $editor = $this->admin('editor', []);

        $this->actingAs($editor, 'admin')->get('/admin/trasparenza/documenti')->assertForbidden();
    }

    public function test_editor_with_trasparenza_permission_can_list_but_not_manage_areas(): void
    {
        $editor = $this->admin('editor', ['trasparenza' => ['read', 'write']]);

        $this->actingAs($editor, 'admin')->get('/admin/trasparenza/documenti')->assertOk();
        $this->actingAs($editor, 'admin')->get('/admin/trasparenza/aree')->assertForbidden();
    }
}
