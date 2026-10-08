<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin', array $perms = [], bool $allCats = true): Admin
    {
        return Admin::create([
            'name' => 'Mario Rossi', 'email' => $role.'@example.test', 'password' => 'x',
            'role' => $role, 'active' => true, 'permissions' => $perms, 'all_categories' => $allCats,
        ]);
    }

    public function test_dashboard_renders_with_content_stats(): void
    {
        $cat = Category::create(['slug' => 'news', 'name' => 'Notizie', 'order' => 0]);
        Post::create(['category_id' => $cat->id, 'slug' => 'p1', 'title' => 'Primo post', 'body' => '<p>x</p>', 'published' => true, 'published_at' => now()]);
        Post::create(['category_id' => $cat->id, 'slug' => 'p2', 'title' => 'Bozza post', 'body' => '<p>x</p>', 'published' => false, 'published_at' => now()]);

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Ciao, Mario')
            ->assertSee('Primo post')
            ->assertSee('Bozza post')
            ->assertSee('Post per categoria');
    }

    public function test_editor_without_pages_permission_sees_no_pages_card(): void
    {
        $editor = $this->admin('editor', ['posts' => ['read']], false);

        $this->actingAs($editor, 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(route('admin.pages.index'));
    }
}
