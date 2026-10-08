<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function libraryCategory(array $attrs = []): DocumentCategory
    {
        return DocumentCategory::create(array_merge([
            'name' => 'Modulistica', 'slug' => 'modulistica', 'order' => 0, 'scope' => 'library', 'selectable' => true,
        ], $attrs));
    }

    private function admin(string $role = 'admin', array $permissions = []): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => $role.'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => $permissions,
        ]);
    }

    public function test_docx_upload_is_accepted_and_stored_with_readable_name(): void
    {
        Storage::fake('public');
        $cat = $this->libraryCategory();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/documenti', [
                'document_category_id' => $cat->id,
                'title' => 'Modulo iscrizione corso',
                'file' => UploadedFile::fake()->create('modulo.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                'published' => '1',
            ])
            ->assertRedirect('/admin/documenti');

        $doc = Document::first();
        $this->assertStringStartsWith('documenti/modulo-iscrizione-corso-', $doc->file_path);
        $this->assertStringEndsWith('.docx', $doc->file_path);
        Storage::disk('public')->assertExists($doc->file_path);
    }

    public function test_document_can_be_uploaded_without_a_category(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->post('/admin/documenti', [
                'title' => 'Volantino evento',
                'file' => UploadedFile::fake()->create('volantino.pdf', 50, 'application/pdf'),
                'published' => '1',
            ])
            ->assertRedirect('/admin/documenti');

        $doc = Document::first();
        $this->assertNull($doc->document_category_id);

        $this->actingAs($admin, 'admin')->get('/admin/documenti?categoria=none')
            ->assertOk()->assertSee('Volantino evento');
        $this->actingAs($admin, 'admin')->get('/admin/documenti/'.$doc->id.'/modifica')->assertOk();
    }

    public function test_executable_upload_is_rejected(): void
    {
        Storage::fake('public');
        $cat = $this->libraryCategory();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/documenti', [
                'document_category_id' => $cat->id,
                'title' => 'X',
                'file' => UploadedFile::fake()->create('evil.exe', 10),
                'published' => '1',
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_upload_to_a_reserved_category_is_rejected(): void
    {
        Storage::fake('public');
        $reserved = $this->libraryCategory(['name' => 'Organigramma', 'slug' => 'organigramma', 'selectable' => false]);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/documenti', [
                'document_category_id' => $reserved->id,
                'title' => 'X',
                'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
                'published' => '1',
            ])
            ->assertSessionHasErrors('document_category_id');
    }

    public function test_library_does_not_expose_trasparenza_documents(): void
    {
        $lib = $this->libraryCategory();
        $tra = DocumentCategory::create(['name' => 'Bilanci', 'slug' => 'bilanci', 'scope' => 'trasparenza']);
        Document::create(['document_category_id' => $lib->id, 'title' => 'DocLib', 'file_path' => 'documenti/l.pdf', 'published' => true]);
        Document::create(['document_category_id' => $tra->id, 'title' => 'DocTrasp', 'file_path' => 'documenti/t.pdf', 'published' => true]);

        $this->actingAs($this->admin(), 'admin')->get('/admin/documenti')
            ->assertOk()->assertSee('DocLib')->assertDontSee('DocTrasp');
    }

    public function test_library_admin_views_render(): void
    {
        $cat = $this->libraryCategory();
        $doc = Document::create([
            'document_category_id' => $cat->id, 'title' => 'Modulo', 'file_path' => 'documenti/m.pdf', 'published' => true,
        ]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get('/admin/documenti/nuovo')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/documenti/'.$doc->id.'/modifica')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/documenti/'.$doc->id)->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/documenti/categorie')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/documenti/categorie/nuova')->assertOk();
        $this->actingAs($admin, 'admin')->get('/admin/documenti/categorie/'.$cat->id.'/modifica')->assertOk();
    }

    public function test_permissions_separate_library_from_trasparenza(): void
    {
        $libEditor = $this->admin('editor', ['documents' => ['read', 'write']]);

        $this->actingAs($libEditor, 'admin')->get('/admin/documenti')->assertOk();
        $this->actingAs($libEditor, 'admin')->get('/admin/trasparenza/documenti')->assertForbidden();
    }
}
