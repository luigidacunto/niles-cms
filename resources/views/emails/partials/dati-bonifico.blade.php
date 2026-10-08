<p style="margin:16px 0 4px"><strong>Come pagare: bonifico bancario</strong></p>
<table cellpadding="4" style="border-collapse:collapse">
    <tr><td><strong>Importo</strong></td><td>{{ $d['importo'] }}@if (! empty($d['dettaglio'])) <span style="color:#555">({{ $d['dettaglio'] }})</span>@endif</td></tr>
    <tr><td><strong>Intestatario</strong></td><td>{{ $d['intestatario'] }}</td></tr>
    <tr><td><strong>IBAN</strong></td><td>{{ $d['iban'] }}</td></tr>
    @if ($d['banca'])<tr><td><strong>Banca</strong></td><td>{{ $d['banca'] }}</td></tr>@endif
    <tr><td><strong>Causale</strong></td><td>{{ $d['causale'] }}</td></tr>
</table>
