<?php

namespace App\Support;

use App\Models\CommitteeInfo;
use App\Models\Corso;
use App\Models\DatiFatturazioneCorso;
use App\Models\IscrizioneCorso;

/**
 * Istruzioni di pagamento dei corsi. Oggi solo il bonifico: dati dal comitato (Dati comitato) e causale compilata
 * ("Corso BLSD-2026-001 - Rossi Mario"). Unico punto che decide *quando* e *a chi* mostrarle (email di iscrizione,
 * pagina personale, riepilogo al referente).
 *
 * Il metodo è un bonifico se corrisponde al metodo `bonifico` del catalogo (config/pagamenti.php; il testo salvato
 * nelle iscrizioni è la sua etichetta, anche in minuscolo nei dati vecchi). Si mostra solo se il corso ha una quota
 * e l'IBAN è impostato.
 *
 * Chi paga: se ognuno paga per sé, le coordinate vanno nell'email di ciascuno; se la fatturazione è unica (un gruppo, o
 * una persona pagata da qualcun altro, es. un'azienda) le riceve chi ha inserito la fatturazione, nel riepilogo, e non
 * gli iscritti — vedi paganoAltri().
 */
class DatiPagamento
{
    /**
     * Coordinate per l'iscritto che paga per sé; null se non deve pagare lui (paga qualcun altro), se si è ritirato o se
     * non c'è nulla da mostrare.
     *
     * @return array{importo:string,dettaglio:?string,intestatario:string,iban:string,banca:?string,causale:string}|null
     */
    public static function bonifico(IscrizioneCorso $iscrizione): ?array
    {
        $corso = $iscrizione->corso;

        if ($iscrizione->ritirata() || self::paganoAltri($iscrizione) || ! self::eBonifico($iscrizione->datiFatturazione) || $corso->costo <= 0) {
            return null;
        }

        return self::coordinate("Corso {$corso->protocollo} - {$iscrizione->cognome} {$iscrizione->nome}", number_format($corso->costo, 2, ',', '.').' €');
    }

    /** Coordinate per chi paga per un gruppo (fatturazione unica): importo totale = quota × persone iscritte con quella fatturazione. */
    public static function bonificoGruppo(DatiFatturazioneCorso $fatturazione, Corso $corso, int $persone): ?array
    {
        if (! self::eBonifico($fatturazione) || $corso->costo <= 0 || $persone < 1) {
            return null;
        }

        $quota = number_format($corso->costo, 2, ',', '.').' €';

        return self::coordinate(
            "Corso {$corso->protocollo} - {$fatturazione->intestatario()}",
            number_format($corso->costo * $persone, 2, ',', '.').' €',
            $persone > 1 ? "{$quota} × {$persone} persone" : null
        );
    }

    /**
     * L'iscritto non paga lui: la fatturazione è condivisa con altre iscrizioni attive dello stesso corso, oppure l'email di
     * contatto della fatturazione non è la sua (l'ha inserita chi l'ha iscritto, es. un'azienda per un dipendente).
     */
    public static function paganoAltri(IscrizioneCorso $iscrizione): bool
    {
        $condivisa = IscrizioneCorso::attive()
            ->where('corso_id', $iscrizione->corso_id)
            ->where('dati_fatturazione_corso_id', $iscrizione->dati_fatturazione_corso_id)
            ->count() > 1;

        $emailFatturazione = $iscrizione->datiFatturazione?->email;
        $diversa = filled($emailFatturazione) && strcasecmp($emailFatturazione, (string) $iscrizione->email) !== 0;

        return $condivisa || $diversa;
    }

    /** Blocco HTML per l'email (stile in linea). Stringa vuota se non c'è nulla da mostrare. */
    public static function bloccoEmail(?array $dati): string
    {
        return $dati ? view('emails.partials.dati-bonifico', ['d' => $dati])->render() : '';
    }

    private static function eBonifico(?DatiFatturazioneCorso $fatturazione): bool
    {
        return mb_strtolower(trim((string) $fatturazione?->metodo_pagamento)) === mb_strtolower(config('pagamenti.metodi.bonifico.label'));
    }

    private static function coordinate(string $causale, string $importo, ?string $dettaglio = null): ?array
    {
        $info = CommitteeInfo::current();

        if (blank($info->iban)) {
            return null;
        }

        return [
            'importo' => $importo,
            'dettaglio' => $dettaglio,
            'intestatario' => $info->intestatario_conto ?: ($info->denominazione ?: config('app.public_name')),
            'iban' => trim(chunk_split($info->iban, 4, ' ')),
            'banca' => $info->banca,
            'causale' => $causale,
        ];
    }
}
