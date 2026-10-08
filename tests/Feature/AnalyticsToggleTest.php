<?php

namespace Tests\Feature;

use App\Models\CommitteeInfo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsToggleTest extends TestCase
{
    use RefreshDatabase;

    private function goProduction(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config([
            'tracking.cookieless.src' => '//stats.example.org/count.js',
            'tracking.cookieless.endpoint' => 'https://stats.example.org/count',
            'tracking.ga.id' => 'G-TEST123',
        ]);
    }

    public function test_both_scripts_load_by_default(): void
    {
        $this->goProduction();

        $response = $this->get('/');

        $response->assertSee('data-goatcounter', false);
        $response->assertSee('G-TEST123', false);
    }

    public function test_goatcounter_hidden_when_toggled_off(): void
    {
        $this->goProduction();
        CommitteeInfo::current()->update(['goatcounter_enabled' => false]);

        $response = $this->get('/');

        $response->assertDontSee('data-goatcounter', false);
        $response->assertSee('G-TEST123', false);
    }

    public function test_ga_hidden_when_toggled_off(): void
    {
        $this->goProduction();
        CommitteeInfo::current()->update(['ga_enabled' => false]);

        $response = $this->get('/');

        $response->assertSee('data-goatcounter', false);
        $response->assertDontSee('G-TEST123', false);
    }
}
