<?php

namespace App\Mail;

use App\Models\CommitteeInfo;
use App\Models\EmailTemplate;
use App\Models\IscrizioneCorso;
use App\Support\DatiPagamento;
use App\Support\IcsEvento;
use Illuminate\Mail\Mailable;

/** Email di iscrizione a un corso: testo dal template `iscrizione-corso` + promemoria .ics in allegato. */
class IscrizioneCorsoMail extends Mailable
{
    public function __construct(public IscrizioneCorso $iscrizione) {}

    public function build(): static
    {
        $iscrizione = $this->iscrizione->loadMissing('corso.tipologia', 'persona', 'datiFatturazione');
        $corso = $iscrizione->corso;

        $mail = EmailTemplate::render('iscrizione-corso', [
            'nome' => $iscrizione->nome,
            'cognome' => $iscrizione->cognome,
            'corso' => $corso->tipologia->nome,
            'protocollo' => $corso->protocollo,
            'periodo' => $corso->periodoLabel(),
            'luogo' => $corso->sedeLabel() ?: 'da comunicare',
            'costo' => $corso->costo > 0 ? number_format($corso->costo, 2, ',', '.').' €' : 'Gratuito',
            'link_preferenze' => route('preferenze.show', $iscrizione->persona->token),
        ], [
            // Coordinate del bonifico: solo se ha scelto il bonifico, il corso ha una quota e l'IBAN è impostato.
            'dati_pagamento' => DatiPagamento::bloccoEmail(DatiPagamento::bonifico($iscrizione)),
        ]);

        // "Rispondi" arriva alla segreteria, non all'indirizzo tecnico di invio.
        if ($segreteria = CommitteeInfo::current()->email) {
            $this->replyTo($segreteria, config('app.public_name'));
        }

        return $this->subject($mail['oggetto'])
            ->html($mail['corpo'])
            ->attachData(IcsEvento::da($iscrizione), 'promemoria-corso.ics', ['mime' => 'text/calendar; charset=UTF-8; method=PUBLISH']);
    }
}
