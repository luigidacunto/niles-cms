<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_production_blocks_robots(): void
    {
        // L'ambiente di test non è production.
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=utf-8')
            ->assertSee("Disallow: /");

        $response = $this->get('/');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->assertSee('Ambiente di prova');
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_production_allows_indexing(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertDontSee('Disallow: /'."\n")
            ->assertSee('Sitemap:');

        $response = $this->get('/');
        $response->assertHeaderMissing('X-Robots-Tag');
        $response->assertDontSee('Ambiente di prova');
        $response->assertDontSee('name="robots"', false);
    }
}
