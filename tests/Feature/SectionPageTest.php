<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionPageTest extends TestCase
{
    use RefreshDatabase;

    private function section(): array
    {
        $category = Category::create(['slug' => 'salute', 'name' => 'Attività - Salute']);
        $page = Page::create([
            'slug' => 'salute', 'title' => 'Salute', 'body' => '<p>Cosa facciamo per la salute.</p>',
            'published' => true, 'in_menu' => true, 'order' => 0, 'system' => true,
            'category_id' => $category->id,
        ]);

        return [$page, $category];
    }

    private function makePost(Category $category, string $title, string $publishedAt, bool $published = true): Post
    {
        return Post::create([
            'category_id' => $category->id, 'slug' => \Illuminate\Support\Str::slug($title), 'title' => $title,
            'body' => '<p>x</p>', 'published' => $published, 'published_at' => $publishedAt,
        ]);
    }

    public function test_section_page_shows_latest_5_posts_newest_first(): void
    {
        [$page, $category] = $this->section();
        foreach (range(1, 7) as $i) {
            $this->makePost($category, "Notizia $i", now()->subDays(10 - $i)->toDateString());
        }
        $this->makePost($category, 'Bozza', now()->toDateString(), published: false);

        $response = $this->get('/salute')->assertOk()->assertSee('Ultime notizie');

        // Le 5 più recenti (7,6,5,4,3), non le vecchie né la bozza.
        $response->assertSee('Notizia 7')->assertSee('Notizia 3')
            ->assertDontSee('Notizia 2')->assertDontSee('Bozza');
        $response->assertSee(route('pages.archive.category', $page));
    }

    public function test_page_without_category_has_no_feed(): void
    {
        Page::create(['slug' => 'normale', 'title' => 'Normale', 'body' => '<p>x</p>', 'published' => true, 'order' => 0]);

        $this->get('/normale')->assertOk()->assertDontSee('Ultime notizie');
    }

    public function test_category_archive_paginates(): void
    {
        [, $category] = $this->section();
        foreach (range(1, 20) as $i) {
            $this->makePost($category, "Notizia $i", now()->subDays($i)->toDateString());
        }

        $this->get('/salute/archivio')->assertOk()->assertSee('Notizia 1');
        $this->get('/salute/archivio?page=2')->assertOk();
    }

    public function test_archive_404_when_page_draft_or_no_category(): void
    {
        [$page] = $this->section();

        $page->update(['published' => false]);
        $this->get('/salute/archivio')->assertNotFound();

        Page::create(['slug' => 'senza', 'title' => 'Senza', 'body' => 'x', 'published' => true, 'order' => 0]);
        $this->get('/senza/archivio')->assertNotFound();
    }
}
