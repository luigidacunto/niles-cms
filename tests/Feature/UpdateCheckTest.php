<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Support\UpdateCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateCheckTest extends TestCase
{
    use RefreshDatabase;

    private $risposta = null;

    /** Un solo fake registrato una volta (i fake successivi di Http::fake() si accodano e non sostituiscono): la risposta è mutabile. */
    private function fakeHttp(array $stubs = []): void
    {
        $this->risposta = $stubs ? reset($stubs) : null;
    }

    private function release(string $tag): void
    {
        $this->fakeHttp(['api.github.com/*' => Http::response([
            'tag_name' => $tag,
            'html_url' => 'https://github.com/luigidacunto/niles-cms/releases/tag/'.$tag,
        ])]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['app.version' => '1.2.0', 'app.update_check.enabled' => true]);
        Http::fake(fn () => $this->risposta ?? Http::response('', 500));
    }

    public function test_reports_newer_release_as_available(): void
    {
        $this->release('v1.3.0');

        $status = UpdateCheck::status();

        $this->assertSame('disponibile', $status['stato']);
        $this->assertSame('1.3.0', $status['versione']);
    }

    public function test_reports_up_to_date_when_same_or_older(): void
    {
        $this->release('v1.2.0');

        $this->assertSame('aggiornato', UpdateCheck::status()['stato']);
    }

    public function test_reports_unavailable_on_failure_and_caches_the_failure(): void
    {
        $this->fakeHttp(['api.github.com/*' => Http::response(['message' => 'Not Found'], 404)]);

        $this->assertSame('non_disponibile', UpdateCheck::status()['stato']);
        $this->assertSame('non_disponibile', UpdateCheck::status()['stato']);
        Http::assertSentCount(1);
    }

    public function test_asks_github_at_most_once_every_24_hours(): void
    {
        $this->release('v1.3.0');

        UpdateCheck::status();
        $this->travel(23)->hours();
        UpdateCheck::status();
        Http::assertSentCount(1);

        $this->travel(2)->hours(); // oltre le 24 ore dalla prima verifica
        UpdateCheck::status();
        Http::assertSentCount(2);
    }

    public function test_ignores_unexpected_payloads(): void
    {
        $this->fakeHttp(['api.github.com/*' => Http::response(['tag_name' => 'nightly', 'html_url' => 'https://evil.example/x'])]);

        $this->assertSame('non_disponibile', UpdateCheck::status()['stato']);
    }

    public function test_does_nothing_when_disabled(): void
    {
        config(['app.update_check.enabled' => false]);
        $this->fakeHttp();

        $this->assertNull(UpdateCheck::status());
        Http::assertNothingSent();
    }

    private function utente(string $ruolo): Admin
    {
        return Admin::create([
            'name' => 'U', 'email' => $ruolo.'@example.test', 'password' => 'x', 'role' => $ruolo, 'active' => true,
            'permissions' => [], 'all_categories' => true,
        ]);
    }

    public function test_dashboard_renders_without_waiting_and_shows_placeholder_to_admins_only(): void
    {
        $this->fakeHttp();

        $this->actingAs($this->utente('admin'), 'admin')->get(route('admin.dashboard'))
            ->assertOk()->assertSee('1.2.0')->assertSee('Verifica…')->assertSee(route('admin.aggiornamenti'), false);
        Http::assertNothingSent(); // la pagina non interroga GitHub: lo fa la rotta in async

        $this->actingAs($this->utente('editor'), 'admin')->get(route('admin.dashboard'))
            ->assertOk()->assertSee('1.2.0')->assertDontSee('Verifica…');
    }

    public function test_route_returns_level_of_available_update(): void
    {
        $admin = $this->utente('admin');

        foreach (['v1.2.1' => 'patch', 'v1.3.0' => 'minor', 'v2.0.0' => 'major'] as $tag => $livello) {
            Cache::flush();
            $this->release($tag);
            $this->actingAs($admin, 'admin')->getJson(route('admin.aggiornamenti'))
                ->assertOk()->assertJson(['stato' => 'disponibile', 'livello' => $livello, 'versione' => ltrim($tag, 'v')]);
        }
    }

    public function test_route_reports_unavailable_up_to_date_and_disabled(): void
    {
        $admin = $this->utente('admin');

        $this->fakeHttp(['api.github.com/*' => Http::response('', 500)]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.aggiornamenti'))->assertOk()->assertJson(['stato' => 'non_disponibile']);

        Cache::flush();
        $this->release('v1.2.0');
        $this->actingAs($admin, 'admin')->getJson(route('admin.aggiornamenti'))->assertOk()->assertJson(['stato' => 'aggiornato']);

        config(['app.update_check.enabled' => false]);
        $this->actingAs($admin, 'admin')->getJson(route('admin.aggiornamenti'))->assertOk()->assertJson(['stato' => 'disattivato']);
    }

    public function test_route_is_reserved_to_admins(): void
    {
        $this->getJson(route('admin.aggiornamenti'))->assertUnauthorized();
        $this->actingAs($this->utente('editor'), 'admin')->getJson(route('admin.aggiornamenti'))->assertForbidden();
    }
}
