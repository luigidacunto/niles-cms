<?php

namespace App\Support;

use RuntimeException;

/**
 * Aggiornamento dal pannello per le installazioni fatte con `scripts/niles-installer.php` (hosting senza riga di
 * comando). Qui sta la fase A, che non tocca il codice in esecuzione: controllo requisiti, download e verifica del
 * pacchetto, estrazione a blocchi in `storage/app/private/update/staging`, token per la fase B. La fase B (sostituzione
 * delle cartelle, migrazioni) la fa il file `niles-installer.php` della versione nuova, fuori dall'applicazione che
 * viene sostituita: vedi `nilesApplyUpdate()` nello script. Le funzioni di rete, zip e file sono quelle dello script
 * (un'unica copia), caricate come libreria.
 */
class WebUpdate
{
    /** Attivo solo con NILES_WEB_UPDATE=true (installer) e mai in demo. */
    public static function attivo(): bool
    {
        return (bool) config('app.web_update') && ! config('app.demo');
    }

    public static function dir(): string
    {
        return storage_path('app/private/update');
    }

    private static function lib(): void
    {
        if (! defined('NILES_INSTALLER_LIB')) {
            define('NILES_INSTALLER_LIB', true);
        }
        require_once base_path('scripts/niles-installer.php');
    }

    /** Versione da installare: l'ultima pubblicata, se più recente di quella installata. */
    public static function versioneDisponibile(): ?string
    {
        $s = UpdateCheck::status();

        return ($s['stato'] ?? null) === 'disponibile' && preg_match('/^\d+\.\d+\.\d+$/', $s['versione']) ? $s['versione'] : null;
    }

    /** @return list<array{0: string, 1: bool, 2: string}> [etichetta, ok, dettaglio] */
    public static function requisiti(): array
    {
        $libero = @disk_free_space(base_path());
        $scrivibili = array_filter(['storage/app/private' => storage_path('app/private'), 'radice del sito' => base_path()], fn ($p) => ! is_writable($p));

        return [
            ['Cartelle scrivibili (aggiornamento e sostituzione del codice)', $scrivibili === [], $scrivibili ? 'non scrivibili: '.implode(', ', array_keys($scrivibili)) : 'ok'],
            ['Estensione zip', class_exists(\ZipArchive::class), 'per aprire il pacchetto'],
            ['Download da internet', function_exists('curl_init') || (bool) ini_get('allow_url_fopen'), 'curl o allow_url_fopen'],
            ['Spazio libero (almeno 500 MB)', $libero === false || $libero > 500 * 1048576, $libero === false ? 'non verificabile' : round($libero / 1048576).' MB'],
            ['Database MySQL (per la copia di sicurezza)', config('database.default') === 'mysql' || config('database.default') === 'mariadb', (string) config('database.default')],
        ];
    }

    /** @return array<string, mixed> */
    public static function stato(): array
    {
        return json_decode((string) @file_get_contents(self::dir().'/stato.json'), true) ?: [];
    }

    private static function salvaStato(array $s): void
    {
        @mkdir(self::dir(), 0755, true);
        file_put_contents(self::dir().'/stato.json', json_encode($s));
    }

    /** Scarica e verifica il pacchetto della versione disponibile, ripulendo ogni preparazione precedente. */
    public static function scarica(): void
    {
        $versione = self::versioneDisponibile() ?? throw new RuntimeException('Nessun aggiornamento disponibile.');
        self::lib();
        $d = self::dir();

        foreach (['staging', 'niles.zip', 'niles.zip.sha256', 'niles.zip.part', 'extract.state', 'token.json', 'stato.json'] as $f) {
            nilesRrmdir("$d/$f");
        }
        @mkdir($d, 0755, true);

        $err = nilesPrepare("$d/niles.zip", "$d/niles.zip.sha256", nilesReleaseUrl('niles.zip', $versione), nilesReleaseUrl('niles.zip.sha256', $versione));
        if ($err !== null) {
            throw new RuntimeException($err);
        }
        self::salvaStato(['versione' => $versione, 'scaricato' => true]);
    }

    /** Un blocco di estrazione. @return array{fatto: bool, i: int, n: int} */
    public static function estrai(): array
    {
        $s = self::stato();
        if (empty($s['scaricato'])) {
            throw new RuntimeException('Pacchetto non scaricato: ripetere la preparazione.');
        }
        self::lib();
        $d = self::dir();

        $r = nilesExtractStep("$d/niles.zip", "$d/staging", "$d/extract.state", 12.0);
        if (is_string($r)) {
            throw new RuntimeException($r);
        }
        if ($r['fatto']) {
            if (! is_file("$d/staging/scripts/niles-installer.php")) {
                throw new RuntimeException('Il pacchetto non contiene il programma di aggiornamento (scripts/niles-installer.php).');
            }
            $s['estratto'] = true;
            self::salvaStato($s);
        }

        return $r;
    }

    /** Registra la copia del database fatta (il nome del file), condizione per poter applicare. */
    public static function segnaBackup(string $nome): void
    {
        $s = self::stato();
        $s['backup'] = $nome;
        self::salvaStato($s);
    }

    /**
     * Fase B: crea il token monouso (15 minuti), copia nella radice il programma di aggiornamento della versione nuova
     * e ritorna l'indirizzo a cui portare il browser.
     */
    public static function applica(): string
    {
        $s = self::stato();
        if (empty($s['estratto']) || empty($s['backup'])) {
            throw new RuntimeException('Preparazione incompleta: servono pacchetto estratto e copia del database.');
        }
        $d = self::dir();
        $token = bin2hex(random_bytes(20));

        if (! copy("$d/staging/scripts/niles-installer.php", base_path('niles-installer.php'))) {
            throw new RuntimeException('Impossibile copiare il programma di aggiornamento nella radice del sito.');
        }
        file_put_contents("$d/token.json", json_encode(['hash' => hash('sha256', $token), 'scade' => time() + 900]));

        return '/niles-installer.php?t='.$token;
    }
}
