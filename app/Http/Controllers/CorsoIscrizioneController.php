<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorsoIscrizioneRequest;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use App\Models\SicurezzaForm;
use App\Support\IscrizioneNotifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CorsoIscrizioneController extends Controller
{
    public function show(Corso $corso)
    {
        abort_unless($corso->pubblicato, 404);

        if (! $corso->iscrizioniAperte()) {
            return view('corsi.iscrizione-non-disponibile', ['corso' => $corso, 'stato' => $corso->statoPubblico()]);
        }

        return view('corsi.iscrizione', ['corso' => $corso, 'sicurezza' => SicurezzaForm::current()]);
    }

    public function store(CorsoIscrizioneRequest $request, Corso $corso)
    {
        abort_unless($corso->pubblicato, 404);
        abort_unless($corso->iscrizioniAperte(), 410, 'Le iscrizioni a questo corso sono chiuse.');

        $data = $request->validated();
        $fatturazioneUnica = $request->boolean('fatturazione_unica');
        $autodichiarazioneAt = $request->boolean('autodichiarazione_terzi') ? now() : null;

        if ($request->fatturazioneComeIscritto()) {
            $data['fatturazione'] = DatiFatturazioneCorso::inputDaNominativo(
                Arr::first($data['nominativi']), $data['fatturazione']['metodo_pagamento'] ?? null
            );
        }

        $iscrizioni = DB::transaction(function () use ($request, $corso, $data, $fatturazioneUnica, $autodichiarazioneAt) {
            $create = collect();
            // Chi compila il modulo è sempre una persona in anagrafica (anche se non frequenta: un HR, un
            // genitore): così lo si può ricontattare, previo consenso. Se frequenta anche lui, è la stessa
            // riga dell'iscritto (stesso codice fiscale).
            $referente = Persona::daDati($data['richiedente']);
            $iscrizioneReferente = null;

            // Il blocco 'fatturazione' non raccoglie email/telefono propri: per il pagamento unico si
            // riusano i contatti del richiedente; per i pagamenti separati ogni persona è fatturata con i propri
            // dati (privato) e i propri contatti.
            $fatturazioneCondivisa = $fatturazioneUnica
                ? DatiFatturazioneCorso::create(DatiFatturazioneCorso::datiDaInput($data['fatturazione'], $data['richiedente']))
                : null;

            foreach ($data['nominativi'] as $nominativo) {
                $fatturazione = $fatturazioneCondivisa
                    ?? DatiFatturazioneCorso::create(DatiFatturazioneCorso::datiDaInput(
                        DatiFatturazioneCorso::inputDaNominativo($nominativo, $nominativo['fatturazione']['metodo_pagamento'] ?? null), $nominativo
                    ));

                $persona = Persona::daDati($nominativo);

                $iscrizione = IscrizioneCorso::create([
                    'corso_id' => $corso->id,
                    'dati_fatturazione_corso_id' => $fatturazione->id,
                    'persona_id' => $persona->id,
                    'referente_persona_id' => $persona->is($referente) ? null : $referente->id,
                    'nome' => $nominativo['nome'],
                    'cognome' => $nominativo['cognome'],
                    'email' => $nominativo['email'],
                    'telefono' => $nominativo['telefono'] ?? null,
                    'codice_fiscale' => strtoupper($nominativo['codice_fiscale']),
                    'via' => $nominativo['via'] ?? null,
                    'comune' => $nominativo['comune'] ?? null,
                    'provincia' => $nominativo['provincia'] ?? null,
                    'cap' => $nominativo['cap'] ?? null,
                    'privacy_accettata_at' => now(),
                    'autodichiarazione_terzi_at' => $autodichiarazioneAt,
                ]);

                $create->push($iscrizione);

                if ($persona->is($referente)) {
                    $iscrizioneReferente = $iscrizione;
                }
            }

            // Le spunte sono di chi compila: newsletter sempre; promemoria attestato solo se frequenta lui
            // stesso il corso (altrimenti l'attestato non è suo). Le persone iscritte da altri non ricevono
            // nulla da qui: decideranno loro dal link nell'email di iscrizione. Una spunta non barrata non
            // revoca mai un consenso già dato.
            if ($request->boolean('consenso_newsletter')) {
                $referente->registraConsenso('newsletter', 'confermato', 'form_pubblico', $iscrizioneReferente);
            }
            if ($request->boolean('consenso_promemoria') && $iscrizioneReferente) {
                $referente->registraConsenso('promemoria', 'confermato', 'form_pubblico', $iscrizioneReferente);
            }

            return [$create, $referente];
        });

        [$iscrizioni, $referente] = $iscrizioni;

        // Dopo il commit: l'iscrizione è già al sicuro anche se un invio fallisce (vedi IscrizioneNotifier).
        IscrizioneNotifier::invia($iscrizioni, $referente);

        return redirect()->route('corsi.iscrizione.confermata', $corso)->with('status', 'Iscrizione registrata.');
    }

    public function confermata(Corso $corso)
    {
        abort_unless(session('status'), 404);

        return view('corsi.iscrizione-confermata', ['corso' => $corso]);
    }
}
