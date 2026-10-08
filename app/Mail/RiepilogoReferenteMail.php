<?php

namespace App\Mail;

use App\Models\CommitteeInfo;
use App\Models\EmailTemplate;
use App\Models\Persona;
use App\Support\DatiPagamento;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

/**
 * Riepilogo e conferma per chi ha iscritto altre persone: chi ha iscritto, i dati inseriti e — con la fatturazione unica —
 * i dati di fatturazione e le coordinate del bonifico (importo totale, causale). Con i pagamenti separati le coordinate
 * non ci sono: ognuno le riceve nella propria email. Template `riepilogo-referente`.
 */
class RiepilogoReferenteMail extends Mailable
{
    /** @param  Collection<int,\App\Models\IscrizioneCorso>  $iscrizioni  tutte quelle create nello stesso invio */
    public function __construct(public Persona $referente, public Collection $iscrizioni) {}

    public function build(): static
    {
        $iscrizioni = $this->iscrizioni->each->loadMissing('corso.tipologia', 'datiFatturazione');
        $corso = $iscrizioni->first()->corso;

        // Fatturazione unica = tutte le iscrizioni dell'invio condividono la stessa riga di fatturazione.
        $idFatturazione = $iscrizioni->pluck('dati_fatturazione_corso_id')->unique();
        $unica = $idFatturazione->count() === 1 ? $iscrizioni->first()->datiFatturazione : null;

        $mail = EmailTemplate::render('riepilogo-referente', [
            'nome' => $this->referente->nome,
            'corso' => $corso->tipologia->nome,
            'protocollo' => $corso->protocollo,
            'periodo' => $corso->periodoLabel(),
            'luogo' => $corso->sedeLabel() ?: 'da comunicare',
            'costo' => $corso->costo > 0 ? number_format($corso->costo, 2, ',', '.').' €' : 'Gratuito',
            'numero_iscritti' => (string) $iscrizioni->count(),
        ], [
            'elenco_iscritti' => view('emails.partials.elenco-iscritti', ['iscrizioni' => $iscrizioni, 'mostraMetodo' => ! $unica])->render(),
            'dati_fatturazione' => view('emails.partials.dati-fatturazione', ['f' => $unica])->render(),
            'dati_pagamento' => $unica ? DatiPagamento::bloccoEmail(DatiPagamento::bonificoGruppo($unica, $corso, $iscrizioni->count())) : '',
        ]);

        if ($segreteria = CommitteeInfo::current()->email) {
            $this->replyTo($segreteria, config('app.public_name'));
        }

        return $this->subject($mail['oggetto'])->html($mail['corpo']);
    }
}
