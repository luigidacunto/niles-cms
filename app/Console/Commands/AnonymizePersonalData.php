<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sostituisce i dati personali reali (soci, persone iscritte ai corsi, dati di fatturazione) con dati fittizi, per
 * poter tenere un database di sviluppo senza dati veri dopo un import da file reali. ⚠️ Irreversibile.
 *
 * - I valori sono deterministici (derivano dall'id): rilanciare il comando dà lo stesso risultato, e iscrizioni e
 *   persona restano coerenti tra loro. Id, ruoli, stati, consensi, date di sistema e collegamenti non cambiano.
 * - Le email fittizie usano un dominio riservato ai test: con il mailer `log` i messaggi finiscono nel log.
 * - In produzione chiede una conferma digitata e `--force` non basta; negli altri ambienti basta un sì/no.
 * - Le persone già anonimizzate dal pannello (`anonimizzata_at`) e le loro iscrizioni non si toccano.
 */
class AnonymizePersonalData extends Command
{
    protected $signature = 'dev:anonymize-personal-data
                            {--domain=example.test : Dominio delle email fittizie}
                            {--force : Salta la conferma (ignorato in produzione)}';

    protected $description = 'Sostituisce con dati fittizi i dati personali di soci, persone e fatturazione dei corsi (solo sviluppo)';

    private const NOMI = [
        'Marco', 'Luca', 'Giovanni', 'Andrea', 'Paolo', 'Stefano', 'Matteo', 'Davide', 'Franco', 'Pietro',
        'John', 'James', 'William', 'Thomas', 'Robert', 'Pierre', 'Louis', 'Antoine', 'Hans', 'Klaus',
        'Jürgen', 'Carlos', 'Miguel', 'Javier', 'Diego', 'Ivan', 'Dmitri', 'Piotr', 'Lars', 'Sven',
        'Ahmed', 'Omar', 'Kenji', 'Hiro', 'Liam', 'Noah', 'Mateo', 'Joao', 'Nikos', 'Tomas',
    ];

    private const COGNOMI = [
        'Rossi', 'Bianchi', 'Verdi', 'Neri', 'Galli', 'Conti', 'Moretti', 'Ferrari', 'Esposito', 'Greco',
        'Smith', 'Brown', 'Taylor', 'Wilson', 'Martin', 'Dubois', 'Laurent', 'Moreau', 'Muller', 'Schmidt',
        'Weber', 'Garcia', 'Lopez', 'Fernandez', 'Silva', 'Costa', 'Ivanov', 'Nowak', 'Kowalski', 'Andersson',
        'Nilsson', 'Haddad', 'Tanaka', 'Suzuki', 'Papadakis', 'Novak', 'Horvat', 'Jansen', 'Visser', 'Larsen',
    ];

    public function handle(): int
    {
        $produzione = app()->environment('production');
        $conn = DB::connection();

        $this->line(sprintf('Ambiente: <info>%s</info> · DB: <info>%s@%s</info>', app()->environment(), $conn->getDatabaseName(), $conn->getConfig('host') ?? 'locale'));
        foreach (['members', 'persone', 'iscrizioni_corso', 'dati_fatturazione_corso'] as $tabella) {
            $this->line(sprintf('  %-26s %d righe', $tabella, $conn->table($tabella)->count()));
        }

        if ($produzione) {
            $this->error('ATTENZIONE: ambiente di PRODUZIONE. I dati personali reali verranno sostituiti in modo irreversibile.');
            if ($this->ask('Per procedere digita "anonimizza produzione"') !== 'anonimizza produzione') {
                $this->warn('Annullato.');

                return self::FAILURE;
            }
        } elseif (! $this->option('force') && ! $this->confirm('Sostituire con dati fittizi i dati personali di questo database? Operazione irreversibile.')) {
            $this->warn('Annullato.');

            return self::FAILURE;
        }

        $domain = $this->option('domain');
        $conteggi = [];

        DB::transaction(function () use ($domain, &$conteggi) {
            $conteggi['members'] = $this->soci($domain);
            $conteggi['login_audit'] = $this->registroAccessi($domain);
            $conteggi['persone'] = $this->persone($domain);
            $conteggi['iscrizioni_corso'] = $this->iscrizioni($domain);
            $conteggi['dati_fatturazione_corso'] = $this->fatturazione($domain);
        });

        foreach ($conteggi as $tabella => $n) {
            $this->line(sprintf('  %-26s %d righe aggiornate', $tabella, $n));
        }
        $this->info('Fatto.');

        return self::SUCCESS;
    }

    /**
     * Identità fittizia derivata da prefisso e id. Il CF ha la struttura reale (6 lettere, 2 cifre, lettera,
     * 2 cifre, lettera, 3 cifre, lettera) ma è palesemente finto: `FK` + sigla tabella + `ANO` + id su 7 cifre.
     *
     * @return array{nome:string,cognome:string,cf:string,telefono:string}
     */
    private function identita(string $sigla, int $id): array
    {
        $d = sprintf('%07d', $id);

        return [
            'nome' => self::NOMI[($id * 7) % count(self::NOMI)],
            'cognome' => self::COGNOMI[($id * 13 + 3) % count(self::COGNOMI)],
            'cf' => "FK{$sigla}ANO".substr($d, 0, 2).'X'.substr($d, 2, 2).'X'.substr($d, 4, 3).'X',
            'telefono' => '3300'.sprintf('%06d', $id % 1000000),
        ];
    }

    /** Valore fittizio solo dove ce n'era uno: i campi vuoti restano vuoti. */
    private function se(mixed $attuale, mixed $nuovo): mixed
    {
        return $attuale === null || $attuale === '' ? $attuale : $nuovo;
    }

    private function soci(string $domain): int
    {
        $n = 0;
        DB::table('members')->orderBy('id')->each(function ($m) use ($domain, &$n) {
            $i = $this->identita('M', $m->id);
            $aggiuntivi = json_decode($m->telefoni_aggiuntivi ?? 'null', true);
            DB::table('members')->where('id', $m->id)->update([
                'nome' => $i['nome'], 'cognome' => $i['cognome'], 'codice_fiscale' => $i['cf'],
                'email' => $this->se($m->email, "socio{$m->id}@{$domain}"),
                'telefono' => $this->se($m->telefono, $i['telefono']),
                'telefoni_aggiuntivi' => is_array($aggiuntivi)
                    ? json_encode(array_map(fn ($k) => '3301'.sprintf('%05d', ($m->id * 10 + $k) % 100000), array_keys($aggiuntivi)))
                    : $m->telefoni_aggiuntivi,
                // Si tiene l'anno; mese e giorno cambiano (giorno ≤ 28: sempre valido).
                'data_nascita' => $m->data_nascita
                    ? sprintf('%04d-%02d-%02d', substr($m->data_nascita, 0, 4), ($m->id * 5) % 12 + 1, ($m->id * 7) % 28 + 1)
                    : null,
            ]);
            $n++;
        });

        return $n;
    }

    /** `label` contiene il nome del socio o l'email digitata: si sostituisce con l'identità fittizia. */
    private function registroAccessi(string $domain): int
    {
        $n = 0;
        $ids = DB::table('login_audit')->where('guard', 'member')->whereNotNull('subject_id')->distinct()->pluck('subject_id');
        foreach ($ids as $id) {
            $i = $this->identita('M', (int) $id);
            $n += DB::table('login_audit')->where('guard', 'member')->where('subject_id', $id)->update(['label' => "{$i['cognome']} {$i['nome']}"]);
        }

        return $n + DB::table('login_audit')->where('guard', 'member')->whereNull('subject_id')->update(['label' => "sconosciuto@{$domain}"]);
    }

    private function persone(string $domain): int
    {
        $n = 0;
        DB::table('persone')->whereNull('anonimizzata_at')->orderBy('id')->each(function ($p) use ($domain, &$n) {
            $i = $this->identita('P', $p->id);
            DB::table('persone')->where('id', $p->id)->update([
                'nome' => $i['nome'], 'cognome' => $i['cognome'], 'codice_fiscale' => $i['cf'],
                'email' => $this->se($p->email, "persona{$p->id}@{$domain}"),
                'cellulare' => $this->se($p->cellulare, $i['telefono']),
            ]);
            $n++;
        });

        return $n;
    }

    /** Le iscrizioni copiano i dati della persona: stessi valori fittizi, calcolati dall'id della persona. */
    private function iscrizioni(string $domain): int
    {
        $n = 0;
        DB::table('iscrizioni_corso')
            ->whereIn('persona_id', fn ($q) => $q->select('id')->from('persone')->whereNull('anonimizzata_at'))
            ->orderBy('id')
            ->each(function ($r) use ($domain, &$n) {
                $i = $this->identita('P', $r->persona_id);
                DB::table('iscrizioni_corso')->where('id', $r->id)->update([
                    'nome' => $i['nome'], 'cognome' => $i['cognome'], 'codice_fiscale' => $i['cf'],
                    'email' => "persona{$r->persona_id}@{$domain}",
                    'telefono' => $this->se($r->telefono, $i['telefono']),
                    'via' => $this->se($r->via, 'Via Fittizia '.($r->persona_id % 200 + 1)),
                ]);
                $n++;
            });

        return $n;
    }

    private function fatturazione(string $domain): int
    {
        $n = 0;
        DB::table('dati_fatturazione_corso')->orderBy('id')->each(function ($f) use ($domain, &$n) {
            $i = $this->identita('B', $f->id);
            DB::table('dati_fatturazione_corso')->where('id', $f->id)->update([
                'nome' => $this->se($f->nome, $i['nome']),
                'cognome' => $this->se($f->cognome, $i['cognome']),
                'ragione_sociale' => $this->se($f->ragione_sociale, "Azienda Fittizia {$f->id} S.r.l."),
                'partita_iva' => $this->se($f->partita_iva, '99'.sprintf('%09d', $f->id % 1000000000)),
                'codice_fiscale' => $this->se($f->codice_fiscale, $i['cf']),
                'via' => $this->se($f->via, 'Via Fittizia '.($f->id % 200 + 1)),
                'pec' => $this->se($f->pec, "pec{$f->id}@{$domain}"),
                'email' => $this->se($f->email, "fatturazione{$f->id}@{$domain}"),
                'telefono' => $this->se($f->telefono, $i['telefono']),
            ]);
            $n++;
        });

        return $n;
    }
}
