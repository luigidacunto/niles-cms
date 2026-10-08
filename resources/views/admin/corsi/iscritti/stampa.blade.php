<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Elenco iscritti — {{ $corso->protocollo }}</title>
    {{-- Pagina autonoma da stampare (nessun menu): elenco per chi tiene il corso, con le caselle da spuntare a mano. --}}
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font: 12px/1.35 Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 16px; }
        h1 { font-size: 18px; margin: 0 0 2px; }
        .meta { color: #333; margin-bottom: 10px; }
        .meta span { margin-right: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 6px 6px; vertical-align: middle; }
        th { background: #eee; text-align: left; font-size: 11px; }
        td.num, th.num { width: 24px; text-align: center; }
        td.casella, th.casella { width: 54px; text-align: center; }
        .box { display: inline-block; width: 16px; height: 16px; border: 1.5px solid #000; vertical-align: middle; }
        td.note { width: 18%; }
        .gruppo { display: block; color: #555; font-size: 10px; }
        .azioni { margin-bottom: 12px; }
        .azioni button { font: 13px Arial; padding: 6px 14px; cursor: pointer; }
        .piede { margin-top: 10px; color: #555; font-size: 10px; }
        tr { page-break-inside: avoid; }
        @media print { .azioni { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="azioni"><button type="button" onclick="window.print()">Stampa</button></div>

    <h1>{{ $corso->tipologia->nome }} — {{ $corso->protocollo }}</h1>
    <div class="meta">
        <span><strong>Quando:</strong> {{ $corso->periodoLabel() }}</span>
        @if ($corso->sedeLabel())<span><strong>Dove:</strong> {{ $corso->sedeLabel() }}</span>@endif
        <span><strong>Quota:</strong> {{ $corso->costo > 0 ? number_format($corso->costo, 2, ',', '.').' €' : 'Gratuito' }}</span>
        <span><strong>Iscritti:</strong> {{ $iscrizioni->count() }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Cognome e nome</th>
                <th>Codice fiscale</th>
                <th>Pagamento</th>
                <th class="casella">Pagato</th>
                <th class="casella">Presente</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($iscrizioni as $i)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td><strong>{{ $i->cognome }}</strong> {{ $i->nome }}</td>
                    <td>{{ $i->codice_fiscale }}</td>
                    <td>{{ $i->datiFatturazione->metodo_pagamento ?: '—' }}
                        @if (in_array($i->dati_fatturazione_corso_id, $condivise, true))
                            <span class="gruppo">pagamento di gruppo: {{ $i->datiFatturazione->intestatario() }}</span>
                        @endif
                    </td>
                    <td class="casella"><span class="box"></span></td>
                    <td class="casella"><span class="box"></span></td>
                    <td class="note"></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:18px">Nessun iscritto attivo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="piede">Elenco stampato il {{ now()->format('d/m/Y H:i') }} — solo iscritti attivi (i ritirati non compaiono).</div>
</body>
</html>
