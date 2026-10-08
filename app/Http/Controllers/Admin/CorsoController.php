<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\SedeCorso;
use App\Models\TipologiaCorso;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CorsoController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('corsi', 'read') || $admin->hasPermission('corsi', 'write'), 403);

        $corsi = Corso::with(['tipologia', 'sede'])->orderByDesc('data_inizio');

        $filters = [
            'q' => trim((string) $request->input('q')),
            'tipologia' => (string) $request->input('tipologia'),
            'stato' => (string) $request->input('stato'), // '' | attivi | annullati | chiusi
        ];

        if ($filters['q'] !== '') {
            $corsi->where('protocollo', 'like', "%{$filters['q']}%");
        }
        if ($filters['tipologia'] !== '') {
            $corsi->where('tipologia_corso_id', $filters['tipologia']);
        }
        if ($filters['stato'] === 'attivi') {
            $corsi->whereNull('annullato_at')->where('chiuso', false);
        } elseif ($filters['stato'] === 'annullati') {
            $corsi->whereNotNull('annullato_at');
        } elseif ($filters['stato'] === 'chiusi') {
            $corsi->where('chiuso', true);
        }

        return view('admin.corsi.index', [
            'corsi' => $corsi->paginate(20)->withQueryString(),
            'filters' => $filters,
            'tipologie' => TipologiaCorso::orderBy('nome')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeWrite($request);

        return view('admin.corsi.form', [
            'corso' => new Corso(['pubblicato' => true]),
            'tipologie' => TipologiaCorso::where('attivo', true)->orderBy('nome')->get(),
            'sedi' => SedeCorso::where('attivo', true)->orderBy('nome')->get(),
            'committeeInfo' => CommitteeInfo::current(),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $this->authorizeWrite($request);
        $data = $this->validated($request);

        $tipologia = TipologiaCorso::findOrFail($data['tipologia_corso_id']);

        $corso = new Corso();
        $this->fillAndSave($corso, $data, $admin, $tipologia);

        return redirect()->route('admin.corsi.index')->with('status', 'Corso creato. Protocollo: '.$corso->protocollo);
    }

    public function edit(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);

        return view('admin.corsi.form', [
            'corso' => $corso,
            'tipologie' => TipologiaCorso::where('attivo', true)->orderBy('nome')->get(),
            'sedi' => SedeCorso::where('attivo', true)->orderBy('nome')->get(),
            'committeeInfo' => CommitteeInfo::current(),
        ]);
    }

    public function show(Request $request, Corso $corso)
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('corsi', 'read') || $admin->hasPermission('corsi', 'write'), 403);

        return view('admin.corsi.show', ['corso' => $corso->load(['tipologia', 'sede', 'creatoDa'])]);
    }

    public function update(Request $request, Corso $corso)
    {
        $admin = $this->authorizeWrite($request);
        $data = $this->validated($request, $corso);

        // Il protocollo, una volta assegnato alla creazione, non cambia più — anche se la tipologia
        // (e quindi la sigla) venisse modificata in seguito.
        $this->fillAndSave($corso, $data, $admin, null);

        return redirect()->route('admin.corsi.index')->with('status', 'Corso aggiornato.');
    }

    public function destroy(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);

        $count = $corso->iscrizioni()->count();
        abort_if($count > 0, 422, "Non puoi eliminare questo corso: ha già {$count} iscritti.");

        $corso->delete();

        return redirect()->route('admin.corsi.index')->with('status', 'Corso eliminato.');
    }

    public function annulla(Request $request, Corso $corso)
    {
        $admin = $this->authorizeWrite($request);
        abort_if($corso->annullato(), 422, 'Questo corso è già annullato.');

        $data = $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        $corso->update([
            'annullato_at' => now(),
            'motivo_annullamento' => $data['motivo'],
            'annullato_da' => $admin->id,
        ]);

        return redirect()->route('admin.corsi.index')->with('status', 'Corso annullato.');
    }

    public function riattiva(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);
        abort_unless($corso->puoRiattivare(), 422, 'Non è più possibile riattivare questo corso: la data prevista è passata.');

        $corso->update(['annullato_at' => null, 'motivo_annullamento' => null, 'annullato_da' => null]);

        return redirect()->route('admin.corsi.index')->with('status', 'Annullamento revocato: il corso è di nuovo attivo.');
    }

    /**
     * 'chiuso' è solo un'etichetta di gestione interna (filtro nell'elenco), non ha alcun effetto sulla
     * pagina pubblica — vedi Corso::statoPubblico().
     */
    public function chiudi(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);
        abort_unless($corso->data_fine->isPast(), 422, 'Puoi chiudere il corso solo dopo la sua data di fine.');
        $corso->update(['chiuso' => true]);

        return redirect()->route('admin.corsi.index')->with('status', 'Corso segnato come chiuso.');
    }

    public function riapri(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);
        $corso->update(['chiuso' => false]);

        return redirect()->route('admin.corsi.index')->with('status', 'Corso riportato tra gli attivi.');
    }

    private function authorizeWrite(Request $request): Admin
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('corsi', 'write'), 403);

        return $admin;
    }

    private function validated(Request $request, ?Corso $corso = null): array
    {
        $data = $request->validate([
            'tipologia_corso_id' => ['required', 'exists:tipologie_corso,id'],
            // 'comitato' | '' | id numerico di una sede esistente | 'nuova'
            'sede_scelta' => ['nullable', 'string', 'max:20'],
            'nuova_sede.nome' => ['required_if:sede_scelta,nuova', 'nullable', 'string', 'max:255'],
            'nuova_sede.via' => ['nullable', 'string', 'max:255'],
            'nuova_sede.comune' => ['nullable', 'string', 'max:255'],
            'nuova_sede.provincia' => ['nullable', 'string', 'max:2'],
            'nuova_sede.cap' => ['nullable', 'string', 'max:5'],
            'data_inizio' => ['required', 'date'],
            'data_fine' => ['required', 'date', 'after_or_equal:data_inizio'],
            'costo' => ['required', 'numeric', 'min:0'],
            'posti_max' => ['nullable', 'integer', 'min:1'],
            'iscrizioni_chiusura_at' => ['nullable', 'date'],
            'descrizione' => ['nullable', 'string'],
            'pubblicato' => ['boolean'],
        ]);

        $data['pubblicato'] = $request->boolean('pubblicato');

        return $data;
    }

    /**
     * Risolve la scelta di sede fatta nel form (select unica: indirizzo del comitato / una sede già
     * salvata / una nuova sede inserita al volo) in (sede_corso_id, usa_indirizzo_comitato). "Nuova
     * sede" crea davvero la riga in sedi_corso qui — da quel momento è una sede normale, riusabile
     * per i corsi successivi. Nessuna pagina/CRUD dedicata: si aggiunge solo così.
     */
    private function risolviSede(array $data): array
    {
        $scelta = $data['sede_scelta'] ?? '';

        if ($scelta === 'comitato') {
            return [null, true];
        }

        if ($scelta === 'nuova') {
            $sede = SedeCorso::create([
                'nome' => $data['nuova_sede']['nome'],
                'via' => $data['nuova_sede']['via'] ?? null,
                'comune' => $data['nuova_sede']['comune'] ?? null,
                'provincia' => $data['nuova_sede']['provincia'] ?? null,
                'cap' => $data['nuova_sede']['cap'] ?? null,
            ]);

            return [$sede->id, false];
        }

        if (ctype_digit((string) $scelta) && SedeCorso::whereKey($scelta)->exists()) {
            return [(int) $scelta, false];
        }

        return [null, false];
    }

    /**
     * Genera il protocollo solo alla creazione (quando $tipologia è passata). Il conteggio +1 non è
     * atomico: se due editor creassero un corso della stessa tipologia nello stesso istante potrebbero
     * calcolare lo stesso progressivo — il vincolo unique su `protocollo` lo intercetta comunque, e qui
     * si ritenta con un nuovo conteggio invece di far fallire la richiesta con un errore 500.
     */
    private function fillAndSave(Corso $corso, array $data, Admin $admin, ?TipologiaCorso $tipologia): void
    {
        [$sedeCorsoId, $usaIndirizzoComitato] = $this->risolviSede($data);

        $corso->tipologia_corso_id = $data['tipologia_corso_id'];
        $corso->sede_corso_id = $sedeCorsoId;
        $corso->usa_indirizzo_comitato = $usaIndirizzoComitato;

        // Data/ora modificabili solo finché il corso non è annullato o segnato come chiuso — il form
        // le disabilita lato UI, ma qui è l'enforcement vero: un valore forgiato nella request viene
        // ignorato, non solo nascosto (stesso principio del blocco "utente protetto" in AdminUsersController).
        if (! $corso->exists || (! $corso->annullato() && ! $corso->chiuso)) {
            $corso->data_inizio = $data['data_inizio'];
            $corso->data_fine = $data['data_fine'];
        }

        $corso->costo = $data['costo'];
        $corso->posti_max = $data['posti_max'] ?? null;
        $corso->iscrizioni_chiusura_at = $data['iscrizioni_chiusura_at'] ?? null;
        $corso->descrizione = $data['descrizione'] ?? null;
        $corso->pubblicato = $data['pubblicato'];

        if (! $corso->exists) {
            $corso->admin_id = $admin->id;
        }

        for ($tentativi = 0; $tentativi < 3; $tentativi++) {
            if ($tipologia && ! $corso->exists) {
                // Lo slug pubblico deriva dal protocollo, non dal titolo: il protocollo è garantito
                // univoco (vincolo DB), il titolo no — due corsi potrebbero chiamarsi allo stesso modo.
                $corso->protocollo = Corso::generaProtocollo($tipologia);
                $corso->slug = \Illuminate\Support\Str::slug($corso->protocollo);
            }

            try {
                $corso->save();

                return;
            } catch (QueryException $e) {
                if (! $tipologia || ! (str_contains($e->getMessage(), 'protocollo') || str_contains($e->getMessage(), 'slug'))) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Impossibile generare un protocollo univoco, riprovare.');
    }
}
