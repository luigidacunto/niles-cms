<?php

namespace App\Services;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Sync dell'anagrafica `members` da un Excel esportato dal gestionale CRI. Due liste indipendenti:
 *  - 'dipendenti' → ruolo dipendente
 *  - 'volontari'  → ruolo volontario | volontario_estensione
 * plan() calcola le operazioni senza scrivere nulla (anteprima); apply() le esegue. Il confirm
 * ricalcola plan() dal file salvato, mai da dati arrivati dal browser.
 */
class MemberImportService
{
    public const OP_NUOVO = 'nuovo';
    public const OP_CAMBIO_RUOLO = 'cambio_ruolo';
    public const OP_RIPRISTINO = 'ripristino';
    public const OP_RIMOSSO = 'rimosso';
    public const OP_INVARIATO = 'invariato';
    public const OP_ERRORE = 'errore';

    public const LISTE = [
        'dipendenti' => [Member::RUOLO_DIPENDENTE],
        'volontari' => [Member::RUOLO_VOLONTARIO, Member::RUOLO_ESTENSIONE],
    ];

    private const REQUIRED = ['Codice Fiscale', 'Cognome', 'Nome', 'Tipo Attuale'];

    /**
     * @return array{blocking: ?string, ops: array<int, array<string, mixed>>, counts: array<string, int>}
     */
    public function plan(string $path, string $lista): array
    {
        $rows = $this->readRows($path);
        if (is_string($rows)) {
            return $this->blocked($rows);
        }

        $ruoli = self::LISTE[$lista];
        $parsed = [];
        $seenCf = [];
        $seenEmail = [];
        $ops = [];

        foreach ($rows as $line => $r) {
            $cf = strtoupper(preg_replace('/\s+/', '', (string) $r['Codice Fiscale']));
            $ruolo = $this->ruoloFromTipo((string) $r['Tipo Attuale']);

            // Il file deve essere della lista dichiarata: un solo valore incoerente blocca tutto (nessun "forza").
            if ($ruolo === null || ! in_array($ruolo, $ruoli, true)) {
                return $this->blocked("Riga $line ({$r['Cognome']} {$r['Nome']}): Tipo Attuale \"{$r['Tipo Attuale']}\" non appartiene alla lista \"$lista\". Controlla di aver scelto il file giusto.");
            }

            if (! preg_match('/^[A-Z0-9]{16}$/', $cf)) {
                $ops[] = $this->op(self::OP_ERRORE, $cf, $r, $ruolo, null, "Riga $line: codice fiscale mancante o non valido.");
                continue;
            }
            if (isset($seenCf[$cf])) {
                $ops[] = $this->op(self::OP_ERRORE, $cf, $r, $ruolo, null, "Riga $line: codice fiscale duplicato nel file (già alla riga {$seenCf[$cf]}).");
                continue;
            }
            $seenCf[$cf] = $line;
            $parsed[$cf] = [$r, $ruolo];
        }

        if ($parsed === []) {
            return $this->blocked('Il file non contiene nessuna riga valida: import annullato per non svuotare la lista.');
        }

        $existing = Member::withTrashed()->whereIn('codice_fiscale', array_keys($parsed))->get()->keyBy('codice_fiscale');

        foreach ($parsed as $cf => [$r, $ruolo]) {
            $m = $existing->get($cf);
            if (! $m) {
                $email = $this->email($r['Email'] ?? null);
                $avviso = null;
                if ($email && (isset($seenEmail[$email]) || Member::withTrashed()->where('email', $email)->exists())) {
                    $avviso = "Email $email già usata da un altro membro: non verrà salvata.";
                    $email = null;
                }
                if ($email) {
                    $seenEmail[$email] = true;
                }
                $ops[] = $this->op(self::OP_NUOVO, $cf, $r, $ruolo, null, $avviso, $email);
            } elseif ($m->trashed()) {
                $ops[] = $this->op(self::OP_RIPRISTINO, $cf, $r, $ruolo, $m);
            } elseif ($m->ruolo !== $ruolo) {
                $ops[] = $this->op(self::OP_CAMBIO_RUOLO, $cf, $r, $ruolo, $m);
            } else {
                $ops[] = $this->op(self::OP_INVARIATO, $cf, $r, $ruolo, $m);
            }
        }

        // Rimozione solo dentro la lista caricata: i membri di altre ruoli non si toccano mai.
        Member::whereIn('ruolo', $ruoli)->whereNotIn('codice_fiscale', array_keys($seenCf))->get()->each(function (Member $m) use (&$ops) {
            $ops[] = [
                'op' => self::OP_RIMOSSO, 'cf' => $m->codice_fiscale, 'cognome' => $m->cognome, 'nome' => $m->nome,
                'ruolo_da' => $m->ruolo, 'ruolo_a' => null, 'member_id' => $m->id, 'avviso' => null,
            ];
        });

        $counts = array_fill_keys([self::OP_NUOVO, self::OP_CAMBIO_RUOLO, self::OP_RIPRISTINO, self::OP_RIMOSSO, self::OP_INVARIATO, self::OP_ERRORE], 0);
        foreach ($ops as $o) {
            $counts[$o['op']]++;
        }

        return ['blocking' => null, 'ops' => $ops, 'counts' => $counts];
    }

    public function apply(array $plan): void
    {
        DB::transaction(function () use ($plan) {
            foreach ($plan['ops'] as $o) {
                switch ($o['op']) {
                    case self::OP_NUOVO:
                        Member::create([
                            'codice_fiscale' => $o['cf'], 'nome' => $o['nome'], 'cognome' => $o['cognome'],
                            'data_nascita' => $o['data_nascita'], 'email' => $o['email'], 'telefono' => $o['telefono'],
                            'telefoni_aggiuntivi' => $o['telefoni_aggiuntivi'], 'ruolo' => $o['ruolo_a'],
                        ]);
                        break;
                    case self::OP_CAMBIO_RUOLO:
                        Member::whereKey($o['member_id'])->update(['ruolo' => $o['ruolo_a']]);
                        break;
                    case self::OP_RIPRISTINO:
                        $m = Member::withTrashed()->findOrFail($o['member_id']);
                        $m->ruolo = $o['ruolo_a'];
                        $m->ripristina();
                        break;
                    case self::OP_RIMOSSO:
                        Member::findOrFail($o['member_id'])->rimuovi();
                        break;
                }
            }
        });
    }

    private function ruoloFromTipo(string $tipo): ?string
    {
        return match (mb_strtolower(trim($tipo))) {
            'dipendente' => Member::RUOLO_DIPENDENTE,
            'volontario' => Member::RUOLO_VOLONTARIO,
            'volontario in estensione' => Member::RUOLO_ESTENSIONE,
            default => null,
        };
    }

    /** @return array<int, array<string, string|null>>|string righe indicizzate per numero di riga Excel, oppure messaggio d'errore */
    private function readRows(string $path): array|string
    {
        try {
            $sheet = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable) {
            return 'Il file non è un Excel leggibile.';
        }

        $header = array_map(fn ($h) => trim((string) $h), array_shift($sheet) ?? []);
        $missing = array_diff(self::REQUIRED, $header);
        if ($missing) {
            return 'Intestazioni mancanti nel file: '.implode(', ', $missing).'.';
        }

        $rows = [];
        foreach ($sheet as $i => $cells) {
            $row = [];
            foreach ($header as $c => $name) {
                $row[$name] = isset($cells[$c]) ? trim((string) $cells[$c]) : null;
            }
            if (implode('', $row) !== '') {
                $rows[$i + 2] = $row; // +2: intestazione alla riga 1, array 0-based
            }
        }

        return $rows;
    }

    private function email(?string $v): ?string
    {
        $v = strtolower(trim((string) $v));

        return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    private function op(string $op, string $cf, array $r, string $ruolo, ?Member $m, ?string $avviso = null, ?string $email = null): array
    {
        $numeri = array_values(array_filter(array_map('trim', explode(',', (string) ($r['Numeri di telefono'] ?? '')))));

        try {
            $nascita = ! empty($r['Data di Nascita']) ? Carbon::createFromFormat('d/m/Y', $r['Data di Nascita'])->startOfDay() : null;
        } catch (\Throwable) {
            $nascita = null;
        }

        return [
            'op' => $op, 'cf' => $cf, 'cognome' => $r['Cognome'], 'nome' => $r['Nome'],
            'ruolo_da' => $m?->ruolo, 'ruolo_a' => $ruolo, 'member_id' => $m?->id, 'avviso' => $avviso,
            // dati usati solo per OP_NUOVO: per i membri esistenti l'import non sovrascrive nulla tranne lo ruolo
            'data_nascita' => $nascita?->toDateString(), 'email' => $email,
            'telefono' => $numeri[0] ?? null, 'telefoni_aggiuntivi' => array_slice($numeri, 1) ?: null,
        ];
    }

    private function blocked(string $msg): array
    {
        return ['blocking' => $msg, 'ops' => [], 'counts' => []];
    }
}
