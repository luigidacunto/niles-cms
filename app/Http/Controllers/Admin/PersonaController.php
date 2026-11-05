<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use App\Support\CsvSicuro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Anagrafica della popolazione dei corsi: elenco con filtri sui consensi, scheda persona, revoca di un consenso,
 * esportazione CSV dei consensi newsletter e anonimizzazione (cancellazione dati, solo admin). Permesso `corsi` in
 * scrittura (chi ha solo la lettura dei corsi non vede l'anagrafica); l'anonimizzazione è riservata al ruolo admin.
 */
class PersonaController extends Controller
{
    /** Colonne esportabili nel CSV: chiave → [intestazione, valore]. Il comitato sceglie quali includere. */
    private const COLONNE_EXPORT = [
        'email' => 'Email',
        'nome' => 'Nome',
        'cognome' => 'Cognome',
        'cellulare' => 'Telefono',
        'codice_fiscale' => 'Codice fiscale',
        'consenso_dal' => 'Consenso dato il',
    ];

    public function index(Request $request)
    {
        $this->authorizeWrite($request);

        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'newsletter' => ['nullable', Rule::in(['confermato', 'richiesto', 'revocato', 'nessuno'])],
            'promemoria' => ['nullable', Rule::in(['confermato', 'richiesto', 'revocato', 'nessuno'])],
            'cancellazione' => ['nullable', Rule::in(['in_attesa', 'anonimizzate'])],
        ]);

        $persone = Persona::query()->withCount('iscrizioni')
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($q) => $q
                ->where('nome', 'like', "%{$t}%")->orWhere('cognome', 'like', "%{$t}%")
                ->orWhere('codice_fiscale', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->when($f['cancellazione'] ?? null, fn ($q, $v) => $v === 'anonimizzate'
                ? $q->whereNotNull('anonimizzata_at')
                : $q->whereNotNull('richiesta_cancellazione_at')->whereNull('anonimizzata_at'),
                fn ($q) => $q->whereNull('anonimizzata_at'))   // di base le anonimizzate non si mostrano
            ->orderBy('cognome')->orderBy('nome');

        foreach (['newsletter', 'promemoria'] as $finalita) {
            $persone->when($f[$finalita] ?? null, fn ($q, $v) => $v === 'nessuno'
                ? $q->whereNull("{$finalita}_stato")
                : $q->where("{$finalita}_stato", $v));
        }

        $attive = Persona::whereNull('anonimizzata_at');

        return view('admin.persone.index', [
            'persone' => $persone->paginate(25)->withQueryString(),
            'filtri' => $f,
            'richieste' => Persona::whereNotNull('richiesta_cancellazione_at')->whereNull('anonimizzata_at')->orderBy('richiesta_cancellazione_at')->get(),
            'totali' => [
                'persone' => (clone $attive)->count(),
                'newsletter' => (clone $attive)->conConsenso('newsletter')->count(),
                'promemoria' => (clone $attive)->conConsenso('promemoria')->count(),
            ],
            'colonneExport' => self::COLONNE_EXPORT,
        ]);
    }

    public function show(Request $request, Persona $persona)
    {
        $this->authorizeWrite($request);

        return view('admin.persone.show', [
            'persona' => $persona,
            'iscrizioni' => $persona->iscrizioni()->with('corso.tipologia', 'referente')->latest()->get(),
            'comeReferente' => $persona->iscrizioniComeReferente()->with('corso.tipologia')->latest()->get(),
            'storico' => $persona->consensi()->orderByDesc('id')->get(),
        ]);
    }

    /** Revoca un consenso (es. richiesta ricevuta per email). Concederlo non si può: solo l'interessato può darlo. */
    public function revoca(Request $request, Persona $persona, string $finalita)
    {
        $this->authorizeWrite($request);
        abort_unless(in_array($finalita, Persona::FINALITA, true) && ! $persona->anonimizzata(), 404);

        if (in_array($persona->statoConsenso($finalita), ['confermato', 'richiesto'], true)) {
            $persona->registraConsenso($finalita, 'revocato', 'admin');
        }

        return back()->with('status', 'Consenso revocato.');
    }

    /** Cancellazione dei dati (irreversibile): solo amministratori. Fatturazione conservata, vedi Persona::anonimizza(). */
    public function anonimizza(Request $request, Persona $persona)
    {
        abort_unless($request->user('admin')->role === 'admin', 403);
        $request->validate(['conferma' => ['accepted']], ['conferma.accepted' => 'Conferma di aver capito che l\'operazione è irreversibile.']);

        $persona->anonimizza();

        return redirect()->route('admin.persone.show', $persona)->with('status', 'Dati anonimizzati.');
    }

    /**
     * Cancella una persona inserita per errore (solo admin). Rifiutato se qualche iscrizione la usa ancora come
     * partecipante o come referente: prima si riagganciano quelle alla persona giusta. Lo storico dei consensi
     * cade con lei (cascata a DB).
     */
    public function destroy(Request $request, Persona $persona)
    {
        abort_unless($request->user('admin')->role === 'admin', 403);

        if (! $persona->eliminabile()) {
            return back()->withErrors(['elimina' => 'Questa persona è ancora collegata a delle iscrizioni (come partecipante o come referente): riagganciale prima a quella corretta.']);
        }

        Log::info('Persona eliminata', ['admin' => $request->user('admin')->email, 'persona_id' => $persona->id, 'cf' => $persona->codice_fiscale, 'nome' => "{$persona->nome} {$persona->cognome}"]);
        $persona->delete();

        return redirect()->route('admin.persone.index')->with('status', 'Persona eliminata.');
    }

    /** Sposta su un'altra persona (già esistente, indicata dal codice fiscale) tutte le iscrizioni di cui questa è referente. Solo admin. */
    public function sostituisciReferente(Request $request, Persona $persona)
    {
        abort_unless($request->user('admin')->role === 'admin', 403);
        $dati = $request->validate(['codice_fiscale' => ['required', 'string', 'size:16']]);

        $nuova = Persona::where('codice_fiscale', strtoupper($dati['codice_fiscale']))->whereNull('anonimizzata_at')->first();
        if (! $nuova || $nuova->is($persona)) {
            return back()->withErrors(['codice_fiscale' => 'Nessun\'altra persona con questo codice fiscale: deve già esistere in anagrafica.']);
        }

        $spostate = DB::transaction(function () use ($persona, $nuova) {
            // Se il nuovo referente è anche il partecipante, il campo torna vuoto (stessa convenzione del modulo pubblico).
            $persona->iscrizioniComeReferente()->where('persona_id', $nuova->id)->update(['referente_persona_id' => null]);

            return $persona->iscrizioniComeReferente()->update(['referente_persona_id' => $nuova->id]);
        });

        Log::info('Referente sostituito', ['admin' => $request->user('admin')->email, 'da_persona_id' => $persona->id, 'a_persona_id' => $nuova->id]);

        return back()->with('status', "Referente sostituito su {$spostate} iscrizioni (ora: {$nuova->nome} {$nuova->cognome}).");
    }

    /** CSV dei contatti con consenso newsletter confermato, con le colonne scelte (adattabile alle esigenze di ogni comitato). */
    public function esporta(Request $request)
    {
        $this->authorizeWrite($request);

        $dati = $request->validate([
            'colonne' => ['required', 'array', 'min:1'],
            'colonne.*' => [Rule::in(array_keys(self::COLONNE_EXPORT))],
            'separatore' => ['nullable', Rule::in([';', ','])],
        ], ['colonne.required' => 'Scegli almeno una colonna da esportare.']);

        $colonne = array_values(array_intersect(array_keys(self::COLONNE_EXPORT), $dati['colonne'])); // ordine fisso
        $sep = $dati['separatore'] ?? ';';

        $persone = Persona::conConsenso('newsletter')->whereNull('anonimizzata_at')->whereNotNull('email')
            ->orderBy('cognome')->orderBy('nome')->get();

        return response()->streamDownload(function () use ($persone, $colonne, $sep) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel apre l'UTF-8 con gli accenti giusti
            fputcsv($out, array_map(fn ($c) => self::COLONNE_EXPORT[$c], $colonne), $sep);

            foreach ($persone as $p) {
                fputcsv($out, array_map(fn ($c) => self::cella($c === 'consenso_dal' ? $p->newsletter_stato_at?->format('d/m/Y') : $p->{$c}), $colonne), $sep);
            }
            fclose($out);
        }, 'newsletter-consensi-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Neutralizza le "formule" nei CSV (celle che iniziano con = + - @) quando si aprono in Excel. */
    private static function cella(?string $valore): string
    {
        return CsvSicuro::cella($valore);
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless($request->user('admin')->hasPermission('corsi', 'write'), 403);
    }
}
