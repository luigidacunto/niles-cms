<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommitteeInfo;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

/**
 * Dati anagrafici/di contatto del comitato + loghi (footer e nav pubblici). Solo admin (middleware
 * `admin.role` sulla rotta) — riga singola, vedi `CommitteeInfo::current()`.
 */
class CommitteeInfoController extends Controller
{
    public function edit()
    {
        return view('admin.comitato.edit', ['info' => CommitteeInfo::current()]);
    }

    public function update(Request $request)
    {
        // IBAN: senza spazi e in maiuscolo prima del controllo (si digita spesso a gruppi di 4).
        $iban = strtoupper(preg_replace('/\s+/', '', (string) $request->input('iban')));
        $request->merge(['iban' => $iban === '' ? null : $iban]);

        $data = $request->validate([
            'iban' => ['nullable', 'regex:/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/'],
            'intestatario_conto' => ['nullable', 'string', 'max:255'],
            'banca' => ['nullable', 'string', 'max:255'],
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
            'logo_orizzontale' => ['nullable', 'image', 'max:4096'],
            'logo_verticale' => ['nullable', 'image', 'max:4096'],
            'logo_ifrc' => ['nullable', 'image', 'max:4096'],
            'logo_un_italia' => ['nullable', 'image', 'max:4096'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:1024'],
            'remove_logo_orizzontale' => ['nullable', 'boolean'],
            'remove_logo_verticale' => ['nullable', 'boolean'],
            'remove_logo_ifrc' => ['nullable', 'boolean'],
            'remove_logo_un_italia' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
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

        // Checkbox: assente nella request = spenta, non "non modificare" — va letta a parte da
        // $request->boolean(), la regola 'nullable' sopra la lascerebbe fuori da $data se non spuntata.
        $data['goatcounter_enabled'] = $request->boolean('goatcounter_enabled');
        $data['ga_enabled'] = $request->boolean('ga_enabled');
        $data['area_soci_attiva'] = $request->boolean('area_soci_attiva');

        $info = CommitteeInfo::current();

        foreach (['logo_orizzontale', 'logo_verticale', 'logo_ifrc', 'logo_un_italia', 'favicon'] as $field) {
            $file = $request->file($field);

            if ($file) {
                if ($info->$field) {
                    Storage::disk('public')->delete($info->$field);
                }
                $data[$field] = ImageUpload::storeResized($file, 'committee', $field, $field === 'favicon' ? 256 : 1200);
            } elseif ($request->boolean("remove_$field") && $info->$field) {
                Storage::disk('public')->delete($info->$field);
                $data[$field] = null;
            } else {
                unset($data[$field]);
            }
        }

        $info->update($data);

        return redirect()->route('admin.comitato.edit')->with('status', 'Dati del comitato aggiornati.');
    }
}
