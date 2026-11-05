<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use Illuminate\Http\Request;

/** Pagamenti dei corsi (solo corsi a pagamento): guida alla fattura, numero/data di fattura e ricevuta, incasso. */
class PagamentoCorsoController extends Controller
{
    public function index()
    {
        $corsi = Corso::with('tipologia')->where('costo', '>', 0)->orderByDesc('data_inizio')->paginate(20);

        // Per ogni corso della pagina: blocchi da fatturare / da incassare (gli stati si ricavano dai blocchi).
        $riepilogo = $corsi->getCollection()->mapWithKeys(function (Corso $corso) {
            $blocchi = $corso->blocchiPagamento();

            return [$corso->id => [
                'blocchi' => $blocchi->count(),
                'da_fatturare' => $blocchi->filter(fn ($r) => ! $r['blocco']->fatturaEmessa())->count(),
                'da_incassare' => $blocchi->filter(fn ($r) => $r['blocco']->statoIncasso($r['totale_cent']) !== 'incassata')->count(),
            ]];
        });

        return view('admin.pagamenti-corsi.index', compact('corsi', 'riepilogo'));
    }

    public function show(Request $request, Corso $corso)
    {
        abort_unless($corso->costo > 0, 404);

        $blocchi = $corso->blocchiPagamento();
        $stato = (string) $request->input('stato'); // '' | da_fatturare | da_incassare

        if ($stato === 'da_fatturare') {
            $blocchi = $blocchi->filter(fn ($r) => ! $r['blocco']->fatturaEmessa())->values();
        } elseif ($stato === 'da_incassare') {
            $blocchi = $blocchi->filter(fn ($r) => $r['blocco']->statoIncasso($r['totale_cent']) !== 'incassata')->values();
        }

        // Iscritti attivi senza dati di fatturazione (es. corso diventato a pagamento dopo le iscrizioni): vanno completati a parte.
        $senzaFatturazione = $corso->iscrizioni()->attive()->whereNull('dati_fatturazione_corso_id')->count();

        return view('admin.pagamenti-corsi.show', compact('corso', 'blocchi', 'stato', 'senzaFatturazione'));
    }

    public function update(Request $request, Corso $corso, DatiFatturazioneCorso $fatturazione)
    {
        $riga = $corso->blocchiPagamento()->first(fn ($r) => $r['blocco']->is($fatturazione));
        abort_unless($riga, 404);

        $data = $request->validate([
            'fattura_numero' => ['nullable', 'string', 'max:50', 'required_with:fattura_data'],
            'fattura_data' => ['nullable', 'date', 'required_with:fattura_numero'],
            'ricevuta_numero' => ['nullable', 'string', 'max:50', 'required_with:ricevuta_data'],
            'ricevuta_data' => ['nullable', 'date', 'required_with:ricevuta_numero'],
            'incassato' => ['nullable', 'boolean'],
            'incassato_importo' => ['nullable', 'required_if:incassato,1', 'numeric', 'min:0', 'max:999999.99'],
            'incasso_nota' => ['nullable', 'string', 'max:1000'],
        ], [
            'fattura_numero.required_with' => 'Indica il numero della fattura insieme alla data.',
            'fattura_data.required_with' => 'Indica la data della fattura insieme al numero.',
            'ricevuta_numero.required_with' => 'Indica il numero della ricevuta insieme alla data.',
            'ricevuta_data.required_with' => 'Indica la data della ricevuta insieme al numero.',
            'incassato_importo.required_if' => "Indica l'importo incassato.",
        ]);

        $incassato = $request->boolean('incassato');
        $nota = trim((string) ($data['incasso_nota'] ?? ''));

        // Importo diverso dal totale dovuto (incasso parziale o in più): la nota che spiega il perché è obbligatoria.
        if ($incassato && (int) round($data['incassato_importo'] * 100) !== $riga['totale_cent'] && $nota === '') {
            return back()->withInput()->withErrors(['incasso_nota' => "L'importo incassato è diverso dal totale dovuto: indica il motivo."]);
        }

        $fatturazione->fill([
            'fattura_numero' => $data['fattura_numero'] ?? null,
            'fattura_data' => $data['fattura_data'] ?? null,
            'ricevuta_numero' => $data['ricevuta_numero'] ?? null,
            'ricevuta_data' => $data['ricevuta_data'] ?? null,
            'incassato_importo' => $incassato ? $data['incassato_importo'] : null,
            // Data dell'incasso = quando è stato registrato la prima volta; una modifica successiva non la sposta.
            'incassato_at' => $incassato ? ($fatturazione->incassato_at ?? now()) : null,
            'incasso_nota' => $incassato && $nota !== '' ? $nota : null,
        ]);

        if ($fatturazione->isDirty()) {
            $fatturazione->fill(['pagamento_registrato_da' => $request->user('admin')->id, 'pagamento_registrato_at' => now()])->save();
        }

        return redirect()->route('admin.pagamenti-corsi.show', $corso)->with('status', "Pagamento di {$fatturazione->intestatario()} aggiornato.");
    }
}
