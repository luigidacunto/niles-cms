<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Support\SeederRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederRegistryTest extends TestCase
{
    use RefreshDatabase;

    private string $cartella;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartella = sys_get_temp_dir().'/niles-seeder-'.uniqid();
        mkdir($this->cartella);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->cartella.'/*.php') ?: []);
        @rmdir($this->cartella);
        parent::tearDown();
    }

    /** Scrive un seeder che crea una categoria (o fallisce). */
    private function scriviSeeder(string $nome, string $slug, bool $fallisce = false): void
    {
        $corpo = $fallisce
            ? 'throw new \RuntimeException("rotto");'
            : '\App\Models\Category::create(["slug" => "'.$slug.'", "name" => "'.$slug.'", "order" => 0]);';
        file_put_contents("{$this->cartella}/{$nome}.php", "<?php return new class extends \Illuminate\Database\Seeder { public function run(): void { {$corpo} } };");
    }

    public function test_esegue_i_pendenti_in_ordine_e_non_li_ripete(): void
    {
        $this->scriviSeeder('2026_11_01_100000_b', 'b');
        $this->scriviSeeder('2026_10_01_100000_a', 'a');
        $registro = new SeederRegistry($this->cartella);

        $this->assertSame(['2026_10_01_100000_a', '2026_11_01_100000_b'], $registro->esegui());
        $this->assertSame(['a', 'b'], Category::orderBy('id')->pluck('slug')->all());
        $this->assertSame([], $registro->esegui());
        $this->assertSame(2, Category::count());
        $this->assertSame([1], DB::table('seeders_eseguiti')->pluck('batch')->unique()->values()->all());
    }

    public function test_un_nuovo_seeder_gira_da_solo_al_giro_successivo(): void
    {
        $this->scriviSeeder('2026_10_01_100000_a', 'a');
        $registro = new SeederRegistry($this->cartella);
        $registro->esegui();

        $this->scriviSeeder('2026_12_01_100000_c', 'c');

        $this->assertSame(['2026_12_01_100000_c'], $registro->esegui());
        $this->assertSame(2, DB::table('seeders_eseguiti')->max('batch'));
    }

    public function test_un_registrato_senza_file_si_ignora(): void
    {
        DB::table('seeders_eseguiti')->insert(['seeder' => '2020_01_01_000000_sparito', 'batch' => 1, 'eseguito_il' => now()]);
        $this->scriviSeeder('2026_10_01_100000_a', 'a');

        $this->assertSame(['2026_10_01_100000_a'], (new SeederRegistry($this->cartella))->esegui());
    }

    public function test_un_seeder_che_fallisce_ferma_tutto_e_non_si_registra(): void
    {
        $this->scriviSeeder('2026_10_01_100000_a', 'a');
        $this->scriviSeeder('2026_10_02_100000_rotto', 'x', fallisce: true);
        $this->scriviSeeder('2026_10_03_100000_c', 'c');
        $registro = new SeederRegistry($this->cartella);

        try {
            $registro->esegui();
            $this->fail('Doveva fallire');
        } catch (\RuntimeException $e) {
            $this->assertSame('rotto', $e->getMessage());
        }

        $this->assertSame(['2026_10_01_100000_a'], array_keys($registro->registrati()));
        $this->assertSame(['a'], Category::pluck('slug')->all());
    }

    public function test_prima_installazione_segna_i_presenti_senza_eseguirli(): void
    {
        $this->scriviSeeder('2026_10_01_100000_a', 'a');
        $registro = new SeederRegistry($this->cartella);

        $registro->segnaTuttiEseguiti();

        $this->assertSame(0, Category::count());
        $this->assertSame([], $registro->esegui());
        $this->assertSame(0, (int) DB::table('seeders_eseguiti')->value('batch'));
    }
}
