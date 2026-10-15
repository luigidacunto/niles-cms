<?php

namespace App\Support;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeder di aggiornamento: contenuti da applicare ai siti già in uso dopo la prima installazione, tracciati come le
 * migrazioni. Ogni file in `database/seeders/aggiornamenti/` si chiama `AAAA_MM_GG_HHMMSS_descrizione.php` (nome
 * univoco, la data ne fissa l'ordine) e restituisce un seeder anonimo (`return new class extends Seeder { ... }`).
 *
 * - Un file non ancora presente in `seeders_eseguiti` viene eseguito (in ordine di nome) e registrato con data e batch.
 * - Una riga registrata il cui file non esiste più si ignora (nessun errore).
 * - Se un seeder fallisce, l'esecuzione si ferma: quelli precedenti restano registrati, quello fallito no (si riprova).
 * - Sulla prima installazione `segnaTuttiEseguiti()` registra i file presenti (batch 0) senza eseguirli: i contenuti
 *   di base (FirstInstallSeeder) sono già aggiornati alla versione corrente.
 *
 * ⚠️ Gira in automatico (deploy, aggiornamento dal pannello): ogni seeder va scritto solo per creare, mai per
 * sovrascrivere ciò che l'installazione ha personalizzato (stessa regola di FirstInstallSeeder).
 */
class SeederRegistry
{
    public const CARTELLA = 'database/seeders/aggiornamenti';

    private string $cartella;

    public function __construct(?string $cartella = null)
    {
        $this->cartella = $cartella ?? base_path(self::CARTELLA);
    }

    /** @return array<string, string> nome => percorso, in ordine di nome */
    public function trovati(): array
    {
        $file = glob($this->cartella.'/*.php') ?: [];
        sort($file);

        $out = [];
        foreach ($file as $f) {
            $out[basename($f, '.php')] = $f;
        }

        return $out;
    }

    /** @return array<string, string> nome => data di esecuzione */
    public function registrati(): array
    {
        return DB::table('seeders_eseguiti')->orderBy('id')->pluck('eseguito_il', 'seeder')->all();
    }

    /** @return array<string, string> nome => percorso dei file non ancora registrati */
    public function pendenti(): array
    {
        return array_diff_key($this->trovati(), $this->registrati());
    }

    /**
     * Esegue i seeder pendenti. @param  callable(string): void|null  $log  chiamata prima di ciascuno
     *
     * @return list<string> nomi eseguiti
     */
    public function esegui(?callable $log = null): array
    {
        $pendenti = $this->pendenti();
        if ($pendenti === []) {
            return [];
        }

        $batch = (int) DB::table('seeders_eseguiti')->max('batch') + 1;
        $fatti = [];
        foreach ($pendenti as $nome => $file) {
            $log && $log($nome);
            $seeder = require $file;
            if (! $seeder instanceof Seeder) {
                throw new RuntimeException("Il file {$nome}.php deve restituire un seeder (return new class extends Seeder { ... }).");
            }
            $seeder->setContainer(app());
            $seeder->__invoke();

            DB::table('seeders_eseguiti')->insert(['seeder' => $nome, 'batch' => $batch, 'eseguito_il' => now()]);
            $fatti[] = $nome;
        }

        return $fatti;
    }

    /** Registra come già coperti (batch 0, senza eseguirli) tutti i file presenti: da chiamare sulla prima installazione. */
    public function segnaTuttiEseguiti(): void
    {
        foreach (array_keys($this->trovati()) as $nome) {
            DB::table('seeders_eseguiti')->insertOrIgnore(['seeder' => $nome, 'batch' => 0, 'eseguito_il' => now()]);
        }
    }
}
