{{-- Dati di fatturazione inseriti dal referente. $f = DatiFatturazioneCorso condivisa, oppure null se ognuno paga per sé. --}}
@if ($f)
    <p style="margin:16px 0 4px"><strong>Dati di fatturazione inseriti (pagamento unico)</strong></p>
    <table cellpadding="4" style="border-collapse:collapse">
        <tr><td><strong>Intestatario</strong></td><td>{{ $f->intestatario() }} ({{ $f->tipo === 'azienda' ? 'azienda / associazione / libero professionista' : 'privato' }})</td></tr>
        @if ($f->partita_iva)<tr><td><strong>Partita IVA</strong></td><td>{{ $f->partita_iva }}</td></tr>@endif
        @if ($f->codice_fiscale)<tr><td><strong>Codice fiscale</strong></td><td>{{ $f->codice_fiscale }}</td></tr>@endif
        @if ($f->via || $f->comune)<tr><td><strong>Indirizzo</strong></td><td>{{ trim("{$f->via}, {$f->cap} {$f->comune}".($f->provincia ? " ({$f->provincia})" : ''), ' ,') }}</td></tr>@endif
        @if ($f->codice_destinatario)<tr><td><strong>Codice destinatario</strong></td><td>{{ $f->codice_destinatario }}</td></tr>@endif
        @if ($f->pec)<tr><td><strong>PEC</strong></td><td>{{ $f->pec }}</td></tr>@endif
        <tr><td><strong>Metodo di pagamento</strong></td><td>{{ $f->metodo_pagamento ?: '—' }}</td></tr>
    </table>
@else
    <p style="margin:16px 0 4px"><strong>Pagamento e fatturazione</strong></p>
    <p style="margin:0">Ogni persona paga per sé e riceve fattura o ricevuta con i propri dati (come privato). Le indicazioni per pagare le arrivano nella sua email di iscrizione.</p>
@endif
