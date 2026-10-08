<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;

/**
 * Pagina personale (/preferenze/{token}): la persona vede i suoi corsi, sceglie i due consensi (promemoria,
 * newsletter) e può chiedere la cancellazione dei dati. Nessun login: il token (64 caratteri casuali) è la
 * chiave. ⚠️ Mai indicizzabile (middleware PaginaPersonale, noindex, robots.txt, niente analytics).
 */
class PreferenzeController extends Controller
{
    public function show(string $token)
    {
        $persona = $this->persona($token);

        return view('preferenze.show', [
            'persona' => $persona,
            'iscrizioni' => $persona->iscrizioni()->with('corso.tipologia', 'datiFatturazione')->latest()->get(),
        ]);
    }

    /**
     * Unico pulsante "Conferma presenza e salva": registra le spunte (stato desiderato: concede se spuntato e non
     * ancora confermato, revoca se tolto e confermato) e conferma la presenza della persona ai suoi corsi non ancora
     * svolti né annullati.
     */
    public function consensi(Request $request, string $token)
    {
        $persona = $this->persona($token);

        foreach (Persona::FINALITA as $finalita) {
            $voluto = $request->boolean("consenso_{$finalita}");

            if ($voluto && ! $persona->haConsenso($finalita)) {
                $persona->registraConsenso($finalita, 'confermato', 'link_personale');
            } elseif (! $voluto && $persona->haConsenso($finalita)) {
                $persona->registraConsenso($finalita, 'revocato', 'link_personale');
            }
        }

        $confermate = $persona->iscrizioni()->whereNull('presenza_confermata_at')->with('corso')->get()
            ->filter->presenzaConfermabile()
            ->each->update(['presenza_confermata_at' => now()])
            ->count();

        // `confermato` (non `status`): la vista mostra la pagina di conferma, senza il modulo dei consensi.
        return redirect()->route('preferenze.show', $token)->with('confermato', $confermate
            ? 'Grazie, abbiamo registrato la tua presenza e le tue scelte.'
            : 'Le tue scelte sono state salvate.');
    }

    /**
     * La richiesta non cancella nulla da sola: segna la richiesta e revoca subito i consensi. Nessuna email al comitato
     * (scelta di progetto): la richiesta compare nel pannello Persone, che si controlla periodicamente.
     */
    public function cancellazione(string $token)
    {
        $persona = $this->persona($token);

        if (! $persona->richiesta_cancellazione_at) {
            $persona->forceFill(['richiesta_cancellazione_at' => now()])->save();

            foreach (Persona::FINALITA as $finalita) {
                if ($persona->haConsenso($finalita)) {
                    $persona->registraConsenso($finalita, 'revocato', 'link_personale');
                }
            }

        }

        return redirect()->route('preferenze.show', $token)->with('status', 'Abbiamo ricevuto la tua richiesta di cancellazione.');
    }

    private function persona(string $token): Persona
    {
        return Persona::where('token', $token)->firstOrFail();
    }
}
