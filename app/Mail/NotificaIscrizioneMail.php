<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Persona;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

/**
 * Avviso alla segreteria (indirizzi in Posta in uscita) per ogni invio del modulo pubblico di iscrizione a un corso.
 * Una sola email per invio, anche con più iscritti. Template `notifica-iscrizione`. ⚠️ Niente codice fiscale né dati di
 * fatturazione: in una casella di posta non servono, si vedono nel pannello.
 */
class NotificaIscrizioneMail extends Mailable
{
    /** @param  Collection<int,\App\Models\IscrizioneCorso>  $iscrizioni  tutte quelle create nello stesso invio */
    public function __construct(public Collection $iscrizioni, public ?Persona $referente = null) {}

    public function build(): static
    {
        $iscrizioni = $this->iscrizioni->each->loadMissing('corso.tipologia');
        $corso = $iscrizioni->first()->corso;

        $mail = EmailTemplate::render('notifica-iscrizione', [
            'corso' => $corso->tipologia->nome,
            'protocollo' => $corso->protocollo,
            'periodo' => $corso->periodoLabel(),
            'numero_iscritti' => (string) $iscrizioni->count(),
            'referente' => $this->referente ? trim("{$this->referente->nome} {$this->referente->cognome} ({$this->referente->email})") : '',
            'link_iscritti' => route('admin.corsi.iscritti.index', $corso),
        ], [
            'elenco_iscritti' => view('emails.partials.elenco-iscritti', ['iscrizioni' => $iscrizioni, 'mostraMetodo' => false, 'senzaCodiceFiscale' => true])->render(),
        ]);

        return $this->subject($mail['oggetto'])->html($mail['corpo']);
    }
}
