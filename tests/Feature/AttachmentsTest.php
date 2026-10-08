<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttachmentsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'x',
            'role' => 'admin', 'active' => true, 'permissions' => [],
        ]);
    }

    private function libraryDocument(): Document
    {
        $cat = DocumentCategory::create(['name' => 'Documenti', 'slug' => 'documenti', 'order' => 0, 'scope' => 'library', 'selectable' => true]);

        return Document::create([
            'document_category_id' => $cat->id, 'title' => 'Modulo iscrizione',
            'file_path' => 'documenti/modulo-iscrizione-xyz.pdf', 'published' => true,
        ]);
    }

    public function test_attaching_a_document_to_a_post_shows_it_publicly(): void
    {
        $category = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);
        $post = Post::create(['category_id' => $category->id, 'slug' => 'p1', 'title' => 'Post', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        $doc = $this->libraryDocument();

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/post/'.$post->id, [
                'category_id' => $category->id, 'title' => 'Post', 'slug' => 'p1', 'body' => '<p>x</p>', 'published' => '1',
                'attach_documents' => [$doc->id],
            ])
            ->assertRedirect('/admin/post');

        $this->assertTrue($post->attachments()->whereKey($doc->id)->exists());

        $this->get('/news/'.$post->fresh()->slug)->assertOk()->assertSee('Modulo iscrizione')->assertSee('Allegati');
    }

    public function test_detaching_a_document_removes_it(): void
    {
        $category = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);
        $post = Post::create(['category_id' => $category->id, 'slug' => 'p2', 'title' => 'Post 2', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        $doc = $this->libraryDocument();
        $post->attachments()->attach($doc->id, ['order' => 0]);

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/post/'.$post->id, [
                'category_id' => $category->id, 'title' => 'Post 2', 'body' => '<p>x</p>', 'published' => '1',
                'attachments' => [$doc->id => ['delete' => '1']],
            ])
            ->assertRedirect('/admin/post');

        $this->assertFalse($post->attachments()->whereKey($doc->id)->exists());
    }

    public function test_attaching_a_document_to_a_page_shows_it_publicly(): void
    {
        $page = Page::create(['slug' => 'pagina-1', 'title' => 'Pagina 1', 'body' => '<p>x</p>', 'published' => true, 'order' => 0]);
        $doc = $this->libraryDocument();

        $this->actingAs($this->admin(), 'admin')
            ->put('/admin/pagine/'.$page->id, [
                'title' => 'Pagina 1', 'body' => '<p>x</p>', 'published' => '1',
                'attach_documents' => [$doc->id],
            ])
            ->assertRedirect('/admin/pagine');

        $this->assertTrue($page->attachments()->whereKey($doc->id)->exists());

        $this->get('/pagina-1')->assertOk()->assertSee('Modulo iscrizione')->assertSee('Allegati');
    }

    public function test_deleting_post_detaches_documents_without_deleting_the_document(): void
    {
        $category = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);
        $post = Post::create(['category_id' => $category->id, 'slug' => 'p3', 'title' => 'Post 3', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        $doc = $this->libraryDocument();
        $post->attachments()->attach($doc->id, ['order' => 0]);

        $this->actingAs($this->admin(), 'admin')->delete('/admin/post/'.$post->id)->assertRedirect('/admin/post');

        $this->assertDatabaseMissing('attachable_documents', ['document_id' => $doc->id]);
        $this->assertDatabaseHas('documents', ['id' => $doc->id]);
    }
}
