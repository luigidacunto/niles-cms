{{-- Elenco delle persone iscritte (riepilogo al referente): $iscrizioni, $mostraMetodo = ognuno paga per sé. --}}
<table cellpadding="5" style="border-collapse:collapse;border:1px solid #ccc;font-size:13px">
    <tr style="background:#f1f1f1;text-align:left">
        <th>Cognome e nome</th><th>Codice fiscale</th><th>Email</th><th>Telefono</th>@if ($mostraMetodo)<th>Pagamento</th>@endif
    </tr>
    @foreach ($iscrizioni as $i)
        <tr style="border-top:1px solid #ddd">
            <td>{{ $i->cognome }} {{ $i->nome }}</td>
            <td>{{ $i->codice_fiscale }}</td>
            <td>{{ $i->email }}</td>
            <td>{{ $i->telefono }}</td>
            @if ($mostraMetodo)<td>{{ $i->datiFatturazione?->metodo_pagamento ?: '—' }}</td>@endif
        </tr>
    @endforeach
</table>
