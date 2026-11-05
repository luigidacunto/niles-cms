<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommitteeInfo;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Riga unica `committee_info` (vedi `CommitteeInfo::current()`), modificata da quattro pagine distinte: dati e social, loghi,
 * coordinate e metodi di pagamento, funzioni attive. Ogni pagina valida e salva solo i propri campi: gli altri restano com'erano.
 * Solo admin (middleware `admin.role` sulle rotte).
 */
class CommitteeInfoController extends Controller
{
    private const LOGHI = ['logo_orizzontale', 'logo_verticale', 'logo_ifrc', 'logo_un_italia', 'favicon'];

    /** Interruttori della pagina Funzioni attive (casella assente = spenta). */
    private const FUNZIONI = ['area_soci_attiva', 'pagamenti_corsi_attivi', 'goatcounter_enabled', 'ga_enabled'];

    public function edit()
    {
        return view('admin.comitato.edit', ['info' => CommitteeInfo::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'denominazione' => ['nullable', 'string', 'max:255'],
            'piva' => ['nullable', 'string', 'max:32'],
            'codice_fiscale' => ['nullable', 'string', 'max:32'],
            'codice_fatturazione_elettronica' => ['nullable', 'string', 'max:32'],
            'telefono' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'pec' => ['nullable', 'email', 'max:255'],
            'indirizzo' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'x_url' => ['nullable', 'url', 'max:255'],
        ]);

        CommitteeInfo::current()->update($data);

        return redirect()->route('admin.comitato.edit')->with('status', 'Dati del comitato aggiornati.');
    }

    public function editLoghi()
    {
        return view('admin.comitato.loghi', ['info' => CommitteeInfo::current()]);
    }

    public function updateLoghi(Request $request)
    {
        $request->validate([
            'logo_orizzontale' => ['nullable', 'image', 'max:4096'],
            'logo_verticale' => ['nullable', 'image', 'max:4096'],
            'logo_ifrc' => ['nullable', 'image', 'max:4096'],
            'logo_un_italia' => ['nullable', 'image', 'max:4096'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:1024'],
            ...collect(self::LOGHI)->mapWithKeys(fn ($f) => ["remove_$f" => ['nullable', 'boolean']])->all(),
        ]);

        $info = CommitteeInfo::current();
        $data = [];

        foreach (self::LOGHI as $field) {
            if ($file = $request->file($field)) {
                if ($info->$field) {
                    Storage::disk('public')->delete($info->$field);
                }
                $data[$field] = ImageUpload::storeResized($file, 'committee', $field, $field === 'favicon' ? 256 : 1200);
            } elseif ($request->boolean("remove_$field") && $info->$field) {
                Storage::disk('public')->delete($info->$field);
                $data[$field] = null;
            }
        }

        $info->update($data);

        return redirect()->route('admin.comitato.loghi.edit')->with('status', 'Loghi aggiornati.');
    }

    public function editPagamenti()
    {
        return view('admin.comitato.pagamenti', ['info' => CommitteeInfo::current()]);
    }

    public function updatePagamenti(Request $request)
    {
        // IBAN: senza spazi e in maiuscolo prima del controllo (si digita spesso a gruppi di 4).
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $request->input('iban')));
        $request->merge(['iban' => $iban === '' ? null : $iban]);

        $data = $request->validate([
            'iban' => ['nullable', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/'],
            'intestatario_conto' => ['nullable', 'string', 'max:255'],
            'banca' => ['nullable', 'string', 'max:255'],
            // Il modulo invia sempre `metodi_pagamento_inviati`: se non c'è nessuna spunta il campo manca e va
            // segnalato; una richiesta senza il marcatore (aggiornamento parziale) lascia i metodi com'erano.
            'metodi_pagamento' => [Rule::requiredIf($request->boolean('metodi_pagamento_inviati')), 'array', 'min:1'],
            'metodi_pagamento.*' => [Rule::in(array_keys(config('pagamenti.metodi')))],
        ], [
            'iban.regex' => 'L\'IBAN non sembra corretto: controlla di averlo scritto bene (es. IT60X0542811101000000123456).',
            'metodi_pagamento.required' => 'Attiva almeno un metodo di pagamento.',
            'metodi_pagamento.min' => 'Attiva almeno un metodo di pagamento.',
        ]);

        if (isset($data['metodi_pagamento'])) {
            $data['metodi_pagamento'] = array_values(array_unique($data['metodi_pagamento']));
        }

        CommitteeInfo::current()->update($data);

        return redirect()->route('admin.comitato.pagamenti.edit')->with('status', 'Coordinate e metodi di pagamento aggiornati.');
    }

    public function editFunzioni()
    {
        return view('admin.comitato.funzioni', ['info' => CommitteeInfo::current()]);
    }

    public function updateFunzioni(Request $request)
    {
        // Interruttori: assente nella richiesta = spento (una casella non spuntata non viene inviata).
        CommitteeInfo::current()->update(collect(self::FUNZIONI)->mapWithKeys(fn ($f) => [$f => $request->boolean($f)])->all());

        return redirect()->route('admin.funzioni.edit')->with('status', 'Funzioni aggiornate.');
    }
}
