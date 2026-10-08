<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Tester', 'email' => 'admin@example.test', 'password' => 'x',
            'role' => 'admin', 'active' => true, 'permissions' => [],
        ]);
    }

    private function category(string $slug = 'news'): Category
    {
        return Category::create(['slug' => $slug, 'name' => ucfirst($slug), 'order' => 0]);
    }

    /**
     * Regressione: ?stato=&categoria= vuoti nell'URL arrivano come null (ConvertEmptyStringsToNull),
     * non devono attivare il filtro categoria (where category_id = null) e nascondere tutto.
     */
    public function test_post_text_filter_works_with_empty_status_and_category_params(): void
    {
        $cat = $this->category();
        Post::create([
            'category_id' => $cat->id, 'slug' => 'inaugurazione-mezzi', 'title' => 'Inaugurazione dei nuovi mezzi',
            'body' => '<p>x</p>', 'published' => true, 'published_at' => now(),
        ]);
        Post::create([
            'category_id' => $cat->id, 'slug' => 'altra-notizia', 'title' => 'Tutt altro',
            'body' => '<p>y</p>', 'published' => true, 'published_at' => now(),
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/post?q=Inaugurazione&stato=&categoria=')
            ->assertOk()
            ->assertSee('Inaugurazione dei nuovi mezzi')
            ->assertDontSee('Tutt altro');
    }

    public function test_post_status_filter_narrows_to_drafts(): void
    {
        $cat = $this->category();
        Post::create(['category_id' => $cat->id, 'slug' => 'pub', 'title' => 'Pubblicato uno', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        Post::create(['category_id' => $cat->id, 'slug' => 'draft', 'title' => 'Bozza uno', 'body' => '<p>x</p>', 'published' => false, 'published_at' => now()]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/post?q=&stato=bozze&categoria=')
            ->assertOk()
            ->assertSee('Bozza uno')
            ->assertDontSee('Pubblicato uno');
    }

    public function test_page_list_stays_a_tree_when_no_filter_and_flattens_when_filtered(): void
    {
        Page::create(['slug' => 'chi-siamo', 'title' => 'Chi Siamo', 'body' => '', 'published' => true, 'order' => 0]);
        Page::create(['slug' => 'contatti', 'title' => 'Contatti', 'body' => '', 'published' => false, 'order' => 1]);

        $admin = $this->admin();

        // Nessun filtro (?menu= vuoto → null): NON deve attivare la modalità filtrata.
        $this->actingAs($admin, 'admin')
            ->get('/admin/pagine?q=&stato=&menu=')
            ->assertOk()
            ->assertDontSee('Elenco filtrato');

        $this->actingAs($admin, 'admin')
            ->get('/admin/pagine?q=contatti')
            ->assertOk()
            ->assertSee('Elenco filtrato')
            ->assertSee('Contatti')
            ->assertDontSee('Chi Siamo');
    }
}
