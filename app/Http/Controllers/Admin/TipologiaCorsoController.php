<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipologiaCorso;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TipologiaCorsoController extends Controller
{
    public function index()
    {
        return view('admin.corsi-catalogo.tipologie.index', [
            'tipologie' => TipologiaCorso::withCount('corsi')->orderBy('order')->orderBy('nome')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.corsi-catalogo.tipologie.form', ['tipologia' => new TipologiaCorso]);
    }

    public function store(Request $request)
    {
        TipologiaCorso::create($this->validated($request));

        return redirect()->route('admin.corsi-tipologie.index')->with('status', 'Tipologia creata.');
    }

    public function edit(TipologiaCorso $tipologiaCorso)
    {
        return view('admin.corsi-catalogo.tipologie.form', ['tipologia' => $tipologiaCorso]);
    }

    public function update(Request $request, TipologiaCorso $tipologiaCorso)
    {
        $tipologiaCorso->update($this->validated($request, $tipologiaCorso));

        return redirect()->route('admin.corsi-tipologie.index')->with('status', 'Tipologia aggiornata.');
    }

    public function destroy(TipologiaCorso $tipologiaCorso)
    {
        $count = $tipologiaCorso->corsi()->count();
        abort_if($count > 0, 422, "Non puoi eliminare questa tipologia: ha ancora {$count} corsi collegati.");

        $tipologiaCorso->delete();

        return redirect()->route('admin.corsi-tipologie.index')->with('status', 'Tipologia eliminata.');
    }

    private function validated(Request $request, ?TipologiaCorso $tipologia = null): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'sigla' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('tipologie_corso', 'sigla')->ignore($tipologia)],
            'rilascia_attestato' => ['boolean'],
            'costo_predefinito' => ['nullable', 'numeric', 'min:0'],
            'attivo' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'immagine' => ['nullable', 'image', 'max:5120'],
            'validita_tipo' => ['nullable', Rule::in(array_keys(TipologiaCorso::VALIDITA_TIPI))],
            'validita_valore' => ['nullable', 'integer', 'min:1', 'max:99', 'required_with:validita_tipo'],
        ]);

        // Validità dell'attestato: senza tipo (o senza valore) l'attestato non scade.
        if (blank($data['validita_tipo'] ?? null) || blank($data['validita_valore'] ?? null)) {
            $data['validita_tipo'] = $data['validita_valore'] = null;
        }

        $data['rilascia_attestato'] = $request->boolean('rilascia_attestato');
        $data['attivo'] = $request->boolean('attivo');

        // Immagine di default della tipologia: nuova => sostituisce (e cancella la vecchia dal disco),
        // "rimuovi" => la toglie. Senza nessuna delle due resta quella attuale.
        unset($data['immagine']);
        if ($request->hasFile('immagine')) {
            $data['immagine'] = ImageUpload::storeResized($request->file('immagine'), 'corsi/tipologie', $data['sigla']);
        } elseif ($request->boolean('rimuovi_immagine')) {
            $data['immagine'] = null;
        }
        if ($tipologia?->immagine && array_key_exists('immagine', $data)) {
            Storage::disk('public')->delete($tipologia->immagine);
        }

        return $data;
    }
}
