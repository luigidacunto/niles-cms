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

    public function test_lettura_checksum(): void
    {
        $h = str_repeat('ab', 32);

        $this->assertSame($h, nilesParseSha("$h  niles.zip\n"));
        $this->assertNull(nilesParseSha('non un hash'));
    }
}
