<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BoardMember;
use App\Models\BoardSetting;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StrutturaOrganizzativaTest extends TestCase
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

    private function page(bool $published = true): Page
    {
        return Page::create([
            'slug' => 'struttura-organizzativa', 'title' => 'Struttura Organizzativa',
            'body' => '<p>intro</p>', 'published' => $published, 'in_menu' => true, 'order' => 0, 'system' => true,
        ]);
    }

    public function test_public_page_renders_members_in_order_and_skips_empty_names(): void
    {
        $this->page();
        BoardMember::create(['slot' => 'member', 'role_label' => 'Consigliere', 'name' => 'Carla Consigliera', 'bio' => 'bio', 'order' => 0]);
        BoardMember::create(['slot' => 'president', 'name' => 'Paolo Presidente', 'bio' => 'bio pres']);
        BoardMember::create(['slot' => 'vice_president', 'name' => 'Vera Vice', 'bio' => 'bio vice']);
        BoardMember::create(['slot' => 'member', 'role_label' => 'Referente', 'name' => '', 'bio' => '', 'order' => 1]);

        $res = $this->get('/struttura-organizzativa')->assertOk();
        $html = $res->getContent();

        $this->assertLessThan(strpos($html, 'Vera Vice'), strpos($html, 'Paolo Presidente'));
        $this->assertLessThan(strpos($html, 'Carla Consigliera'), strpos($html, 'Vera Vice'));
        $res->assertSee('Presidente')->assertSee('Vice Presidente')->assertSee('Consigliere');
    }

    public function test_unpublished_page_is_404(): void
    {
        $this->page(published: false);
        $this->get('/struttura-organizzativa')->assertNotFound();
    }

    public function test_admin_section_is_admin_only(): void
    {
        $this->page();
        $this->actingAs($this->admin('editor', ['pages' => ['write']]), 'admin')
            ->get('/admin/struttura-organizzativa')->assertForbidden();
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/struttura-organizzativa')->assertOk();
    }

    public function test_update_creates_president_and_a_free_block(): void
    {
        $this->page();

        $this->actingAs($this->admin(), 'admin')->put('/admin/struttura-organizzativa', [
            'slots' => [
                'president' => ['name' => 'Paolo Presidente', 'bio' => 'Guida il comitato.'],
                'vice_president' => ['name' => '', 'bio' => ''],
            ],
            'members' => [
                ['role_label' => 'Consigliere Giovani', 'name' => 'Giulia', 'bio' => 'Rappresenta i giovani.'],
            ],
        ])->assertRedirect('/admin/struttura-organizzativa');

        $this->assertDatabaseHas('board_members', ['slot' => 'president', 'name' => 'Paolo Presidente']);
        $this->assertDatabaseHas('board_members', ['slot' => 'member', 'role_label' => 'Consigliere Giovani', 'name' => 'Giulia']);
        $this->assertDatabaseMissing('board_members', ['slot' => 'vice_president']);
    }

    public function test_photo_upload_is_resized_and_stored_with_a_readable_name(): void
    {
        Storage::fake('public');
        $this->page();

        $this->actingAs($this->admin(), 'admin')->put('/admin/struttura-organizzativa', [
            'slots' => [
                'president' => [
                    'name' => 'Paolo Presidente',
                    'bio' => 'Guida il comitato.',
                    'photo' => UploadedFile::fake()->image('foto originale.jpg', 1200, 900),
                ],
                'vice_president' => ['name' => '', 'bio' => ''],
            ],
            'members' => [],
        ])->assertRedirect('/admin/struttura-organizzativa');

        $photo = BoardMember::where('slot', 'president')->value('photo_path');
        $this->assertStringStartsWith('board-photos/paolo-presidente-presidente-', $photo);
        Storage::disk('public')->assertExists($photo);
        [$w, $h] = getimagesize(Storage::disk('public')->path($photo));
        $this->assertSame(800, $w); // scaleDown a 800 sul lato lungo
        $this->assertSame(600, $h);
    }

    public function test_name_without_bio_fails_and_saves_nothing(): void
    {
        $this->page();

        $this->actingAs($this->admin(), 'admin')->put('/admin/struttura-organizzativa', [
            'slots' => [
                'president' => ['name' => 'Paolo', 'bio' => ''],
                'vice_president' => ['name' => '', 'bio' => ''],
            ],
            'members' => [],
        ])->assertSessionHasErrors('slots.president.bio');

        $this->assertDatabaseCount('board_members', 0);
    }

    public function test_clearing_president_name_removes_the_row(): void
    {
        $this->page();
        BoardMember::create(['slot' => 'president', 'name' => 'Vecchio', 'bio' => 'bio']);

        $this->actingAs($this->admin(), 'admin')->put('/admin/struttura-organizzativa', [
            'slots' => [
                'president' => ['name' => '', 'bio' => ''],
                'vice_president' => ['name' => '', 'bio' => ''],
            ],
            'members' => [],
        ])->assertRedirect('/admin/struttura-organizzativa');

        $this->assertDatabaseMissing('board_members', ['slot' => 'president']);
    }

    public function test_organigramma_upload_creates_reserved_document_and_shows_on_page(): void
    {
        Storage::fake('public');
        $this->page();

        $this->actingAs($this->admin(), 'admin')->put('/admin/struttura-organizzativa', [
            'slots' => ['president' => ['name' => '', 'bio' => ''], 'vice_president' => ['name' => '', 'bio' => '']],
            'members' => [],
            'organigramma' => UploadedFile::fake()->create('organigramma.pdf', 100, 'application/pdf'),
        ])->assertRedirect('/admin/struttura-organizzativa');

        $cat = DocumentCategory::where('slug', 'organigramma')->first();
        $this->assertFalse($cat->selectable);
        $doc = Document::where('document_category_id', $cat->id)->first();
        $this->assertNotNull($doc);
        $this->assertSame($doc->id, BoardSetting::current()->organigramma_document_id);

        $this->get('/struttura-organizzativa')->assertOk()->assertSee('Organigramma del Comitato');
    }

    public function test_removing_organigramma_unlinks_but_keeps_the_document(): void
    {
        Storage::fake('public');
        $this->page();
        $cat = DocumentCategory::create(['name' => 'Organigramma', 'slug' => 'organigramma', 'scope' => 'library', 'selectable' => false]);
        $doc = Document::create(['document_category_id' => $cat->id, 'title' => 'Organigramma 2025', 'file_path' => 'documenti/o.pdf', 'published' => true]);
        BoardSetting::current()->update(['organigramma_document_id' => $doc->id]);

        $this->actingAs($this->admin(), 'admin')->put('/admin/struttura-organizzativa', [
            'slots' => ['president' => ['name' => '', 'bio' => ''], 'vice_president' => ['name' => '', 'bio' => '']],
            'members' => [],
            'remove_organigramma' => '1',
        ])->assertRedirect('/admin/struttura-organizzativa');

        $this->assertNull(BoardSetting::current()->organigramma_document_id);
        $this->assertDatabaseHas('documents', ['id' => $doc->id]); // resta in libreria
    }
}
