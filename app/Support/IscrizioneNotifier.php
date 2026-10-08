<?php

namespace App\Support;

use App\Mail\IscrizioneCorsoMail;
use App\Mail\RiepilogoReferenteMail;
use App\Models\IscrizioneCorso;
use App\Models\Persona;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Invia a ogni persona iscritta (da sola, da un referente o inserita a mano dal pannello) l'email di
 * iscrizione con il promemoria per il calendario e il link alla pagina personale. Nello stesso passaggio
 * registra la *richiesta* di consenso per le finalità su cui la persona non ha ancora deciso: l'email è l'invito
 * a decidere. ⚠️ Un invio fallito non deve mai far perdere l'iscrizione: si scrive un warning e si va avanti.
 */
class IscrizioneNotifier
{
    /**
     * @param  iterable<IscrizioneCorso>  $iscrizioni
     * @param  Persona|null  $referente  chi ha compilato il modulo: se ha iscritto anche altri, riceve il riepilogo
     */
    public static function invia(iterable $iscrizioni, ?Persona $referente = null): void
    {
        $iscrizioni = collect($iscrizioni);

        foreach ($iscrizioni as $iscrizione) {
            try {
                Mail::to($iscrizione->email)->send(new IscrizioneCorsoMail($iscrizione));
            } catch (Throwable $e) {
                Log::warning("Email di iscrizione #{$iscrizione->id} non inviata: ".$e->getMessage());

                continue; // senza email partita, nessuna "richiesta di consenso" da registrare
            }

            $persona = $iscrizione->persona;
            foreach (['promemoria', 'newsletter'] as $finalita) {
                if ($persona->statoConsenso($finalita) === null) {
                    $persona->registraConsenso($finalita, 'richiesto', 'email_iscrizione', $iscrizione);
                }
            }
        }

        // Riepilogo/conferma a chi ha iscritto altre persone (se si è iscritto solo lui, ha già la sua email).
        if ($referente && $referente->email && $iscrizioni->contains(fn ($i) => $i->persona_id !== $referente->id)) {
            try {
                Mail::to($referente->email)->send(new RiepilogoReferenteMail($referente, $iscrizioni));
            } catch (Throwable $e) {
                Log::warning("Riepilogo al referente #{$referente->id} non inviato: ".$e->getMessage());
            }
        }
    }
}
