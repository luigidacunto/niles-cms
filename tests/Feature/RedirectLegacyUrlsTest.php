<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectLegacyUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_op_when_config_disabled(): void
    {
        config(['legacy_redirects' => ['exact' => [], 'category_map' => [], 'post_pattern' => false]]);

        $this->get('/pagina-inesistente-xyz')->assertNotFound();
    }

    public function test_exact_match_redirects_301(): void
    {
        config(['legacy_redirects' => [
            'exact' => ['/vecchia-pagina' => '/nuova-pagina'],
            'category_map' => [],
            'post_pattern' => false,
        ]]);

        $this->get('/vecchia-pagina')->assertRedirect('/nuova-pagina')->assertStatus(301);
    }

    public function test_post_pattern_redirects_with_category_remap(): void
    {
        config(['legacy_redirects' => [
            'exact' => [],
            'category_map' => ['sociale' => 'inclusione-sociale'],
            'post_pattern' => true,
        ]]);

        $this->get('/post/sociale/qualche-slug')
            ->assertRedirect('/inclusione-sociale/qualche-slug')
            ->assertStatus(301);
    }

    public function test_exact_match_wins_over_post_pattern(): void
    {
        config(['legacy_redirects' => [
            'exact' => ['/post/news/vecchio-slug' => '/archivi/archivio-notizie'],
            'category_map' => [],
            'post_pattern' => true,
        ]]);

        $this->get('/post/news/vecchio-slug')
            ->assertRedirect('/archivi/archivio-notizie')
            ->assertStatus(301);
    }
}
