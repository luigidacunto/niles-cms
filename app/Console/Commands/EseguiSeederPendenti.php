<?php

namespace App\Console\Commands;

use App\Support\SeederRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Esegue i seeder di aggiornamento non ancora registrati (vedi App\Support\SeederRegistry). Si lancia dopo
 * `migrate` nel deploy e dall'aggiornamento dal pannello; con --stato elenca senza eseguire.
 */
class EseguiSeederPendenti extends Command
{
    protected $signature = 'niles:seeders {--stato : Elenca i seeder di aggiornamento senza eseguirli}';

    protected $description = 'Esegue i seeder di aggiornamento non ancora eseguiti (database/seeders/aggiornamenti)';

    public function handle(SeederRegistry $registro): int
    {
        if (! Schema::hasTable('seeders_eseguiti')) {
            $this->warn('Tabella seeders_eseguiti assente: eseguire prima le migrazioni.');

            return self::FAILURE;
        }

        if ($this->option('stato')) {
            $registrati = $registro->registrati();
            $trovati = $registro->trovati();
            foreach ($trovati as $nome => $_) {
                $this->line(sprintf('%-60s %s', $nome, isset($registrati[$nome]) ? 'eseguito '.$registrati[$nome] : '<comment>da eseguire</comment>'));
            }
            foreach (array_diff_key($registrati, $trovati) as $nome => $quando) {
                $this->line(sprintf('%-60s <fg=gray>file assente (ignorato), eseguito %s</>', $nome, $quando));
            }

            return self::SUCCESS;
        }

        try {
            $fatti = $registro->esegui(fn (string $nome) => $this->line("> $nome"));
        } catch (Throwable $e) {
            $this->error('Seeder fallito: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info($fatti === [] ? 'Nessun seeder da eseguire.' : count($fatti).' seeder eseguiti.');

        return self::SUCCESS;
    }
}
