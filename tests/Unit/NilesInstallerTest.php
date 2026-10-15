<?php

namespace Tests\Unit;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

/**
 * Funzioni pure dell'installer per hosting condiviso (scripts/niles-installer.php): scrittura di `.env` e
 * validazione dello zip. Il file, in CLI, definisce solo le funzioni e non avvia il wizard.
 */
class NilesInstallerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__.'/../../scripts/niles-installer.php';
    }

    private function config(?array $smtp = null): array
    {
        return [
            'public_name' => 'Croce Rossa – Comitato "Esempio" #1',
            'url' => 'https://esempio.it',
            'key' => 'base64:abc=',
            'db' => ['host' => 'localhost', 'port' => '3306', 'name' => 'db', 'user' => 'u', 'pass' => 'p@ss"w$ord #1\\x y'],
            'smtp' => $smtp,
        ];
    }

    public function test_env_con_caratteri_speciali_si_rilegge_identico(): void
    {
        $env = Dotenv::parse(nilesBuildEnv($this->config()));

        $this->assertSame('p@ss"w$ord #1\\x y', $env['DB_PASSWORD']);
        $this->assertSame('Croce Rossa – Comitato "Esempio" #1', $env['APP_PUBLIC_NAME']);
        $this->assertSame('base64:abc=', $env['APP_KEY']);
        $this->assertSame('production', $env['APP_ENV']);
        $this->assertSame('sync', $env['QUEUE_CONNECTION']);
        $this->assertSame('log', $env['MAIL_MAILER']);
    }

    public function test_env_con_smtp(): void
    {
        $smtp = ['host' => 'smtps.example.it', 'port' => '465', 'enc' => 'ssl', 'user' => 'a@esempio.it', 'pass' => 'x y$', 'from' => 'a@esempio.it', 'from_name' => 'Comitato'];
        $env = Dotenv::parse(nilesBuildEnv($this->config($smtp)));

        $this->assertSame('smtp', $env['MAIL_MAILER']);
        $this->assertSame('smtps', $env['MAIL_SCHEME']);
        $this->assertSame('x y$', $env['MAIL_PASSWORD']);

        $env = Dotenv::parse(nilesBuildEnv($this->config(['enc' => 'tls'] + $smtp)));
        $this->assertArrayNotHasKey('MAIL_SCHEME', $env);
    }

    public function test_voci_dello_zip_non_ammesse(): void
    {
        foreach (['app/Models/Admin.php', '.htaccess', 'vendor/a/b.php', 'storage/'] as $ok) {
            $this->assertTrue(nilesZipEntrySafe($ok), $ok);
        }
        foreach (['../x.php', 'a/../../b', '/etc/passwd', 'C:\\x', 'a\\b', "a\0b", ''] as $ko) {
            $this->assertFalse(nilesZipEntrySafe($ko), $ko);
        }
    }

    public function test_cartelle_negate_dal_build_non_coincidono_con_voci_di_public(): void
    {
        $script = file_get_contents(__DIR__.'/../../scripts/build-release.sh');
        $this->assertSame(1, preg_match('/^for d in (.+); do$/m', $script, $m));

        foreach (explode(' ', $m[1]) as $cartella) {
            $primo = explode('/', $cartella)[0];
            // `app` ↔ public/app non esiste; `storage/...` ↔ public/storage è lecito solo per sottocartelle mai pubblicate.
            if ($primo === 'storage') {
                $this->assertNotContains(basename($cartella), ['public', 'post-covers', 'post-images', 'post-body', 'committee', 'board-photos', 'documenti']);
                continue;
            }
            $this->assertFileDoesNotExist(__DIR__.'/../../public/'.$primo, "{$cartella} è negata dal build ma public/{$primo} esiste: gli asset pubblici darebbero 403");
        }
    }

    public function test_env_abilita_l_aggiornamento_dal_pannello(): void
    {
        $this->assertSame('true', Dotenv::parse(nilesBuildEnv($this->config()))['NILES_WEB_UPDATE']);
    }

    /** @var list<string> */
    private array $temporanee = [];

    protected function tearDown(): void
    {
        array_map('nilesRrmdir', $this->temporanee);
        parent::tearDown();
    }

    /** Crea un albero di file temporaneo: ['percorso/relativo' => 'contenuto']. */
    private function albero(array $file): string
    {
        $d = sys_get_temp_dir().'/niles-test-'.uniqid();
        foreach ($file as $rel => $contenuto) {
            @mkdir(dirname("$d/$rel"), 0755, true);
            file_put_contents("$d/$rel", $contenuto);
        }
        @mkdir($d, 0755, true);

        return $this->temporanee[] = $d;
    }

    public function test_sostituzione_delle_cartelle_di_codice_conserva_il_resto(): void
    {
        $root = $this->albero(['app/vecchio.php' => 'v', 'vendor/v.php' => 'v', 'public/build/a.js' => 'v', 'public/index.php' => 'v', '.env' => 'SEGRETO', 'storage/app/dato.txt' => 'dato']);
        $staging = $this->albero(['app/nuovo.php' => 'n', 'vendor/n.php' => 'n', 'public/build/b.js' => 'n', 'public/index.php' => 'n', 'README.md' => 'n']);
        $old = $this->albero([]);

        $fatte = nilesSwapCode($root, $staging, $old);
        nilesCopyTree($staging, $root);

        $this->assertSame(['app', 'vendor', 'public/build'], $fatte);
        $this->assertFileExists("$root/app/nuovo.php");
        $this->assertFileDoesNotExist("$root/app/vecchio.php");
        $this->assertFileExists("$old/app/vecchio.php");
        $this->assertFileExists("$root/public/build/b.js");
        $this->assertSame('n', file_get_contents("$root/public/index.php"));
        $this->assertFileExists("$root/README.md");
        $this->assertSame('SEGRETO', file_get_contents("$root/.env"));
        $this->assertSame('dato', file_get_contents("$root/storage/app/dato.txt"));
    }

    public function test_se_la_sostituzione_fallisce_si_ripristina_tutto(): void
    {
        $root = $this->albero(['app/vecchio.php' => 'v', 'config/vecchio.php' => 'v']);
        $staging = $this->albero(['app/nuovo.php' => 'n', 'config/nuovo.php' => 'n']);
        $old = $this->albero(['config/occupato.txt' => 'x']);   // blocca lo spostamento di config (cartella non vuota)

        try {
            nilesSwapCode($root, $staging, $old);
            $this->fail('Doveva fallire');
        } catch (\RuntimeException) {
        }

        $this->assertFileExists("$root/app/vecchio.php");
        $this->assertFileDoesNotExist("$root/app/nuovo.php");
        $this->assertFileExists("$root/config/vecchio.php");
        $this->assertFileExists("$staging/app/nuovo.php");
        $this->assertFileDoesNotExist("$old/app");
    }

    public function test_token_monouso_valido_solo_se_coincide_e_non_e_scaduto(): void
    {
        $root = $this->albero([]);
        @mkdir("$root/storage/app/private/update", 0755, true);
        $scrivi = fn (int $scade) => file_put_contents("$root/storage/app/private/update/token.json", json_encode(['hash' => hash('sha256', 'segreto'), 'scade' => $scade]));

        $scrivi(time() + 600);
        $this->assertNotNull(nilesTokenValid($root, 'segreto'));
        $this->assertNull(nilesTokenValid($root, 'sbagliato'));
        $this->assertNull(nilesTokenValid($root, ''));

        $scrivi(time() - 1);
        $this->assertNull(nilesTokenValid($root, 'segreto'));
    }

    public function test_estrazione_a_passi_riprende_da_dove_era_arrivata(): void
    {
        $dir = $this->albero([]);
        $zip = "$dir/p.zip";
        $z = new \ZipArchive();
        $z->open($zip, \ZipArchive::CREATE);
        for ($i = 0; $i < 700; $i++) {
            $z->addFromString("d/f$i.txt", (string) $i);
        }
        $z->close();

        $r = nilesExtractStep($zip, "$dir/out", "$dir/stato", 0.0);   // budget 0: un solo blocco per chiamata
        $this->assertFalse($r['fatto']);
        $this->assertSame(300, $r['i']);
        $r = nilesExtractStep($zip, "$dir/out", "$dir/stato", 0.0);
        $r = nilesExtractStep($zip, "$dir/out", "$dir/stato", 0.0);

        $this->assertTrue($r['fatto']);
        $this->assertCount(700, glob("$dir/out/d/*.txt"));
        $this->assertFileDoesNotExist("$dir/stato");
    }

    public function test_lettura_checksum(): void
    {
        $h = str_repeat('ab', 32);

        $this->assertSame($h, nilesParseSha("$h  niles.zip\n"));
        $this->assertNull(nilesParseSha('non un hash'));
    }
}
