<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SicurezzaForm;
use Illuminate\Http\Request;

/** Interruttori anti-bot dei moduli pubblici (oggi solo iscrizione corsi). Solo admin, riga singola. */
class SicurezzaFormController extends Controller
{
    public function edit()
    {
        return view('admin.sicurezza-form.edit', ['impostazioni' => SicurezzaForm::current()]);
    }

    public function update(Request $request)
    {
        $impostazioni = SicurezzaForm::current();

        $data = $request->validate([
            'turnstile_site_key' => ['nullable', 'string', 'max:255'],
            'turnstile_secret_key' => ['nullable', 'string', 'max:255'],
        ] + collect(SicurezzaForm::LIMITI)->mapWithKeys(fn ($c) => ["limite_{$c}" => ['nullable', 'integer', 'min:1', 'max:1000']])->all());

        // La secret non viene mai rimostrata nel form: campo vuoto = tieni quella già salvata.
        $secret = filled($data['turnstile_secret_key'] ?? null) ? $data['turnstile_secret_key'] : $impostazioni->turnstile_secret_key;
        $siteKey = $data['turnstile_site_key'] ?? null;
        $captcha = $request->boolean('captcha_attivo');

        if ($captcha && (blank($siteKey) || blank($secret))) {
            return back()->withInput()->withErrors(['captcha_attivo' => 'Per attivare il CAPTCHA servono sia la site key sia la secret key.']);
        }

        $impostazioni->update([
            'rate_limiting_attivo' => $request->boolean('rate_limiting_attivo'),
            'captcha_attivo' => $captcha,
            'turnstile_site_key' => $siteKey,
            'turnstile_secret_key' => $secret,
        ] + collect(SicurezzaForm::LIMITI)->mapWithKeys(fn ($c) => ["limite_{$c}" => $request->integer("limite_{$c}") ?: null])->all());

        return redirect()->route('admin.sicurezza-form.edit')->with('status', 'Impostazioni aggiornate.');
    }
}
