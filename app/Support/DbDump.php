<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Copia di sicurezza del database fatta con PHP (PDO), per l'aggiornamento dal pannello su hosting senza `mysqldump`.
 *
 * Solo MySQL/MariaDB. Il file `.sql` (struttura + righe, `INSERT` a blocchi) sta in `storage/app/private/backups/`,
 * una cartella che non è mai servita dal web. Si produce a passi brevi (`passo()`, entro un budget di secondi) con lo
 * stato in un file, così la pagina può mostrare l'avanzamento senza dipendere dal tempo massimo della richiesta.
 * Si tengono solo gli ultimi esemplari (contiene dati personali). Il ripristino è manuale (importazione del file).
 */
class DbDump
{
    /** Tabelle di cui si salva solo la struttura: dati temporanei, inutili in un ripristino. */
    private const SENZA_DATI = ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    private const RIGHE_PER_BLOCCO = 200;

    public static function cartella(): string
    {
        return storage_path('app/private/backups');
    }

    private static function statoFile(): string
    {
        return storage_path('app/private/update/dump.json');
    }

    /** Avvia un nuovo dump: elenca le tabelle, crea il file con l'intestazione. @return array<string, mixed> stato */
    public static function inizia(): array
    {
        $pdo = DB::connection()->getPdo();
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new RuntimeException('La copia del database è disponibile solo per MySQL/MariaDB.');
        }
        $tabelle = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

        @mkdir(self::cartella(), 0755, true);
        @file_put_contents(self::cartella().'/.htaccess', "Require all denied\n");
        $file = sprintf('niles-%s-%s.sql', date('Ymd-His'), bin2hex(random_bytes(4)));
        file_put_contents(self::cartella().'/'.$file, "-- NILES: copia del database ".DB::connection()->getDatabaseName().' del '.date('c')."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $stato = ['file' => $file, 'tabelle' => $tabelle, 't' => 0, 'offset' => 0, 'struttura' => false, 'fatto' => false];
        self::salva($stato);

        return $stato;
    }

    /** Un passo: scrive tabelle/righe finché dura il budget. @return array<string, mixed> stato aggiornato */
    public static function passo(float $budget = 12.0): array
    {
        $stato = json_decode((string) @file_get_contents(self::statoFile()), true);
        if (! is_array($stato) || ! preg_match('/^niles-[0-9-]+-[a-f0-9]{8}\.sql$/', $stato['file'] ?? '')) {
            throw new RuntimeException('Nessuna copia del database in corso.');
        }
        if ($stato['fatto']) {
            return $stato;
        }

        $pdo = DB::connection()->getPdo();
        $out = fopen(self::cartella().'/'.$stato['file'], 'ab');
        $t0 = microtime(true);
        $n = count($stato['tabelle']);

        while ($stato['t'] < $n && microtime(true) - $t0 < $budget) {
            $tabella = $stato['tabelle'][$stato['t']];
            $q = '`'.str_replace('`', '``', $tabella).'`';

            if (! $stato['struttura']) {
                $crea = $pdo->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1];
                fwrite($out, "DROP TABLE IF EXISTS $q;\n$crea;\n\n");
                $stato['struttura'] = true;
            }

            $righe = in_array($tabella, self::SENZA_DATI, true) ? [] : self::blocco($pdo, $q, $stato['offset']);
            if ($righe === []) {
                $stato['t']++;
                $stato['offset'] = 0;
                $stato['struttura'] = false;

                continue;
            }
            fwrite($out, self::insertSql($pdo, $tabella, array_keys($righe[0]), $righe));
            $stato['offset'] += count($righe);
        }

        if ($stato['t'] >= $n) {
            fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
            $stato['fatto'] = true;
            self::pulisci();
        }
        fclose($out);
        self::salva($stato);

        return $stato;
    }

    /** @return list<array<string, mixed>> */
    private static function blocco(PDO $pdo, string $tabella, int $offset): array
    {
        $chiavi = $pdo->query("SHOW KEYS FROM $tabella WHERE Key_name = 'PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
        usort($chiavi, fn ($a, $b) => $a['Seq_in_index'] <=> $b['Seq_in_index']);
        $ordine = $chiavi ? ' ORDER BY '.implode(', ', array_map(fn ($c) => '`'.str_replace('`', '``', $c['Column_name']).'`', $chiavi)) : '';

        return $pdo->query(sprintf('SELECT * FROM %s%s LIMIT %d OFFSET %d', $tabella, $ordine, self::RIGHE_PER_BLOCCO, $offset))->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * `INSERT` multi-riga. @param  list<string>  $colonne
     *
     * @param  list<array<string, mixed>>  $righe
     */
    public static function insertSql(PDO $pdo, string $tabella, array $colonne, array $righe): string
    {
        $id = fn (string $n) => '`'.str_replace('`', '``', $n).'`';
        $valore = fn ($v) => match (true) {
            $v === null => 'NULL',
            is_bool($v) => $v ? '1' : '0',
            is_int($v), is_float($v) => (string) $v,
            default => $pdo->quote((string) $v),
        };
        $valori = array_map(fn (array $r) => '('.implode(',', array_map($valore, array_values($r))).')', $righe);

        return 'INSERT INTO '.$id($tabella).' ('.implode(',', array_map($id, $colonne)).") VALUES\n".implode(",\n", $valori).";\n\n";
    }

    /** Copie disponibili, la più recente per prima. @return list<array{nome: string, dimensione: int, data: int}> */
    public static function elenco(): array
    {
        $out = [];
        foreach (glob(self::cartella().'/niles-*.sql') ?: [] as $f) {
            $out[] = ['nome' => basename($f), 'dimensione' => (int) filesize($f), 'data' => (int) filemtime($f)];
        }
        usort($out, fn ($a, $b) => strcmp($b['nome'], $a['nome']));

        return $out;
    }

    /** Percorso di una copia esistente (il nome è validato: niente percorsi arbitrari). */
    public static function percorso(string $nome): ?string
    {
        if (! preg_match('/^niles-[0-9-]+-[a-f0-9]{8}\.sql$/', $nome)) {
            return null;
        }
        $p = self::cartella().'/'.$nome;

        return is_file($p) ? $p : null;
    }

    /** Conserva solo le ultime copie (contengono dati personali). */
    public static function pulisci(int $tieni = 2): void
    {
        foreach (array_slice(self::elenco(), $tieni) as $vecchia) {
            @unlink(self::cartella().'/'.$vecchia['nome']);
        }
    }

    /** @param  array<string, mixed>  $stato */
    private static function salva(array $stato): void
    {
        @mkdir(dirname(self::statoFile()), 0755, true);
        file_put_contents(self::statoFile(), json_encode($stato));
    }
}
