<?php

namespace App\Http\Controllers\Admin;

use App\Exports\IscrittiCorsoExport;
use App\Http\Controllers\Controller;
use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Rules\CampoAnagrafico;
use App\Rules\CodiceFiscale;
use App\Rules\PartitaIva;
use App\Support\CsvSicuro;
use App\Support\Pulizia;
use App\Support\IscrizioneNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CorsoIscrizioneController extends Controller
{
    public function index(Request $request, Corso $corso)
    {
        $this->authorizeRead($request);

        $stato = $this->stato($request);

        return view('admin.corsi.iscritti.index', [
            'corso' => $corso,
            'stato' => $stato,
            'iscrizioni' => $this->elenco($corso, $stato)->with('datiFatturazione')->get(),
            'conteggi' => [
                'attivi' => $corso->iscrizioni()->attive()->count(),
                'ritirati' => $corso->iscrizioni()->ritirate()->count(),
            ],
        ]);
    }

    /** Elenco stampabile per chi tiene il corso: nome, codice fiscale, pagamento, caselle da spuntare a mano. Solo iscritti attivi. */
    public function stampa(Request $request, Corso $corso)
    {
        $this->authorizeRead($request);

        $iscrizioni = $corso->iscrizioni()->attive()->with('datiFatturazione')->orderBy('cognome')->orderBy('nome')->get();

        return view('admin.corsi.iscritti.stampa', [
            'corso' => $corso->load('tipologia'),
            'iscrizioni' => $iscrizioni,
            // Fatturazioni condivise da più iscritti (pagamento unico di gruppo): si segnala chi paga per tutti.
            'condivise' => $iscrizioni->countBy('dati_fatturazione_corso_id')->filter(fn ($n) => $n > 1)->keys()->all(),
        ]);
    }

    /** Ritira l'iscritto dal corso (non lo cancella): sparisce dagli elenchi operativi, si può ripristinare. */
    public function ritira(Request $request, Corso $corso, IscrizioneCorso $iscrizione)
    {
        $this->authorizeWrite($request);
        abort_unless($iscrizione->corso_id === $corso->id, 404);

        $nota = trim((string) $request->input('nota'));
        $iscrizione->forceFill(['ritirata_at' => $iscrizione->ritirata_at ?? now(), 'nota_ritiro' => $nota !== '' ? mb_substr($nota, 0, 255) : $iscrizione->nota_ritiro])->save();

        return back()->with('status', "{$iscrizione->nome} {$iscrizione->cognome} è stato ritirato dal corso.");
    }

    public function ripristina(Request $request, Corso $corso, IscrizioneCorso $iscrizione)
    {
        $this->authorizeWrite($request);
        abort_unless($iscrizione->corso_id === $corso->id, 404);

        // Se nel frattempo la stessa persona si è iscritta di nuovo, non si crea un doppione.
        $doppione = $corso->iscrizioni()->attive()->where('codice_fiscale', $iscrizione->codice_fiscale)->where('id', '!=', $iscrizione->id)->exists();
        if ($doppione) {
            return back()->withErrors(['ripristina' => 'Questa persona risulta già iscritta di nuovo al corso: non si può ripristinare un secondo iscritto.']);
        }

        $iscrizione->forceFill(['ritirata_at' => null, 'nota_ritiro' => null])->save();

        return back()->with('status', "{$iscrizione->nome} {$iscrizione->cognome} è di nuovo iscritto al corso.");
    }

    /** `attivi` (default) | `ritirati` | `tutti`: filtro di elenco ed export. */
    private function stato(Request $request): string
    {
        return in_array($request->query('stato'), ['ritirati', 'tutti'], true) ? $request->query('stato') : 'attivi';
    }

    private function elenco(Corso $corso, string $stato)
    {
        $q = $corso->iscrizioni()->orderBy('cognome')->orderBy('nome');

        return match ($stato) {
            'ritirati' => $q->ritirate(),
            'tutti' => $q,
            default => $q->attive(),
        };
    }

    /** Inserimento manuale (es. iscrizione raccolta telefonicamente o di persona), stesso schema dati del form pubblico. */
    public function create(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);

        // Fatturazioni già presenti nel corso (un gruppo con pagamento unico ne condivide una sola).
        $fatturazioniEsistenti = DatiFatturazioneCorso::whereHas('iscrizioni', fn ($q) => $q->where('corso_id', $corso->id))
            ->withCount(['iscrizioni' => fn ($q) => $q->where('corso_id', $corso->id)])
            ->get();

        return view('admin.corsi.iscritti.form', compact('corso', 'fatturazioniEsistenti'));
    }

    public function store(Request $request, Corso $corso)
    {
        $this->authorizeWrite($request);

        // Dati puliti prima di validarli e salvarli (maiuscole/minuscole uniformi, spazi): vedi App\Support\Pulizia.
        $request->merge(array_filter([
            'nominativo' => is_array($request->input('nominativo')) ? Pulizia::persona($request->input('nominativo')) : null,
            'fatturazione' => is_array($request->input('fatturazione')) ? Pulizia::fatturazione($request->input('fatturazione')) : null,
        ], fn ($v) => $v !== null));

        $metodiPagamento = CommitteeInfo::current()->metodi_pagamento;
        // 'stessi' = fatturazione al partecipante, 'esistente' = riga già usata nel corso (condivisa col
        // gruppo, metodo di pagamento ereditato), 'manuale' = dati inseriti a mano.
        $modo = $request->input('fatturazione_modo', 'stessi');
        $comeIscritto = $modo === 'stessi';
        $esistente = $modo === 'esistente';

        $data = $request->validate([
            'nominativo.nome' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'nominativo.cognome' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'nominativo.email' => ['required', 'email', 'max:255'],
            'nominativo.telefono' => ['required', 'string', 'max:30', new CampoAnagrafico('telefono')],
            'nominativo.codice_fiscale' => ['required', new CodiceFiscale],
            'nominativo.via' => ['required', 'string', 'max:255', new CampoAnagrafico('via')],
            'nominativo.comune' => ['required', 'string', 'max:255', new CampoAnagrafico('nome')],
            'nominativo.provincia' => ['required', 'string', new CampoAnagrafico('provincia')],
            'nominativo.cap' => ['required', 'string', new CampoAnagrafico('cap')],

            'fatturazione_modo' => ['nullable', Rule::in(['stessi', 'esistente', 'manuale'])],
            'fatturazione_esistente_id' => [
                Rule::requiredIf($esistente),
                Rule::exists('iscrizioni_corso', 'dati_fatturazione_corso_id')->where('corso_id', $corso->id),
            ],

            'fatturazione.tipo' => [Rule::requiredIf($modo === 'manuale'), Rule::in(['privato', 'azienda'])],
            'fatturazione.nome' => ['required_if:fatturazione.tipo,privato', 'nullable', 'string', 'max:255', new CampoAnagrafico('nome')],
            'fatturazione.cognome' => ['required_if:fatturazione.tipo,privato', 'nullable', 'string', 'max:255', new CampoAnagrafico('nome')],
            'fatturazione.ragione_sociale' => ['required_if:fatturazione.tipo,azienda', 'nullable', 'string', 'max:255'],
            'fatturazione.partita_iva' => ['required_if:fatturazione.tipo,azienda', 'nullable', new PartitaIva],
            'fatturazione.codice_fiscale' => ['nullable', new CodiceFiscale],
            'fatturazione.via' => ['nullable', 'string', 'max:255', new CampoAnagrafico('via')],
            'fatturazione.comune' => ['nullable', 'string', 'max:255', new CampoAnagrafico('nome')],
            'fatturazione.provincia' => ['nullable', 'string', new CampoAnagrafico('provincia')],
            'fatturazione.cap' => ['nullable', 'string', new CampoAnagrafico('cap')],
            'fatturazione.codice_destinatario' => ['nullable', 'regex:/^[A-Za-z0-9]{6,7}$/'],
            'fatturazione.pec' => ['nullable', 'email', 'max:255'],
            'fatturazione.metodo_pagamento' => ['nullable', Rule::in($metodiPagamento)],

            // Nessuna spunta di consenso newsletter/promemoria qui: la persona li sceglie dal link nell'email
            // di iscrizione (un consenso dato a voce non è documentabile).
            'privacy_confermata' => ['accepted'],
        ], [
            'fatturazione.codice_destinatario.regex' => 'Il codice destinatario deve essere di 7 caratteri (6 per la pubblica amministrazione), solo lettere e numeri.',
            'fatturazione.pec.email' => 'La PEC deve essere un indirizzo email valido.',
        ], [
            'nominativo.nome' => 'nome',
            'nominativo.cognome' => 'cognome',
            'nominativo.email' => 'email',
            'nominativo.telefono' => 'telefono',
            'nominativo.codice_fiscale' => 'codice fiscale',
            'nominativo.via' => 'via / largo / piazza / località',
            'nominativo.comune' => 'comune',
            'nominativo.provincia' => 'provincia',
            'nominativo.cap' => 'CAP',
            'fatturazione.nome' => 'nome di fatturazione',
            'fatturazione.cognome' => 'cognome di fatturazione',
            'fatturazione.via' => 'via / largo / piazza / località di fatturazione',
            'fatturazione.comune' => 'comune di fatturazione',
            'fatturazione.provincia' => 'provincia di fatturazione',
            'fatturazione.cap' => 'CAP di fatturazione',
        ]);

        if ($corso->iscrizioni()->attive()->where('codice_fiscale', strtoupper($data['nominativo']['codice_fiscale']))->exists()) {
            return back()->withInput()->withErrors(['nominativo.codice_fiscale' => 'Questa persona risulta già iscritta a questo corso.']);
        }

        if ($comeIscritto) {
            $data['fatturazione'] = DatiFatturazioneCorso::inputDaNominativo(
                $data['nominativo'], $data['fatturazione']['metodo_pagamento'] ?? null
            );
        }

        $fatturazione = $esistente
            ? DatiFatturazioneCorso::findOrFail($data['fatturazione_esistente_id'])
            : DatiFatturazioneCorso::create(DatiFatturazioneCorso::datiDaInput($data['fatturazione'], $data['nominativo']));

        $persona = Persona::daDati($data['nominativo']);

        $iscrizione = IscrizioneCorso::create([
            'corso_id' => $corso->id,
            'dati_fatturazione_corso_id' => $fatturazione->id,
            'persona_id' => $persona->id,
            'nome' => $data['nominativo']['nome'],
            'cognome' => $data['nominativo']['cognome'],
            'email' => $data['nominativo']['email'],
            'telefono' => $data['nominativo']['telefono'] ?? null,
            'codice_fiscale' => strtoupper($data['nominativo']['codice_fiscale']),
            'via' => $data['nominativo']['via'] ?? null,
            'comune' => $data['nominativo']['comune'] ?? null,
            'provincia' => $data['nominativo']['provincia'] ?? null,
            'cap' => $data['nominativo']['cap'] ?? null,
            'privacy_accettata_at' => now(),
        ]);

        // Anche l'iscritto a mano riceve l'email (promemoria calendario + link ai consensi).
        IscrizioneNotifier::invia([$iscrizione]);

        return redirect()->route('admin.corsi.iscritti.index', $corso)->with('status', 'Iscritto aggiunto: gli abbiamo inviato l\'email di iscrizione.');
    }

    public function export(Request $request, Corso $corso, string $formato)
    {
        $this->authorizeRead($request);

        $nomeFile = "iscritti-{$corso->protocollo}";
        $stato = $this->stato($request);

        return match ($formato) {
            'xlsx' => Excel::download(new IscrittiCorsoExport($corso, $stato), "{$nomeFile}.xlsx"),
            'pdf' => Pdf::loadView('admin.corsi.iscritti.pdf', [
                'corso' => $corso,
                'iscrizioni' => $this->elenco($corso, $stato)->with('datiFatturazione')->get(),
            ])->download("{$nomeFile}.pdf"),
            default => $this->exportCsv($corso, $nomeFile, $stato),
        };
    }

    private function exportCsv(Corso $corso, string $nomeFile, string $stato)
    {
        $iscrizioni = $this->elenco($corso, $stato)->with('datiFatturazione')->get();

        return response()->streamDownload(function () use ($iscrizioni) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Nome', 'Cognome', 'Email', 'Telefono', 'Codice fiscale', 'Fatturazione a', 'Metodo pagamento', 'Presenza confermata', 'Ritirato il', 'Iscritto il']);
            foreach ($iscrizioni as $i) {
                // Telefono: già ristretto a numeri e + ( ) . - (non può contenere formule); gli altri testi arrivano dal pubblico.
                fputcsv($out, array_merge(array_map([CsvSicuro::class, 'cella'], [$i->nome, $i->cognome, $i->email]), [$i->telefono], array_map([CsvSicuro::class, 'cella'], [
                    $i->codice_fiscale,
                    $i->datiFatturazione->tipo === 'azienda' ? $i->datiFatturazione->ragione_sociale : trim("{$i->datiFatturazione->nome} {$i->datiFatturazione->cognome}"),
                    $i->datiFatturazione->metodo_pagamento,
                ]), [
                    $i->presenza_confermata_at?->format('d/m/Y H:i'),
                    $i->ritirata_at?->format('d/m/Y H:i'),
                    $i->created_at->format('d/m/Y H:i'),
                ]));
            }
            fclose($out);
        }, "{$nomeFile}.csv");
    }

    private function authorizeRead(Request $request): void
    {
        $admin = $request->user('admin');
        abort_unless($admin->hasPermission('corsi', 'read') || $admin->hasPermission('corsi', 'write'), 403);
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless($request->user('admin')->hasPermission('corsi', 'write'), 403);
    }
}
