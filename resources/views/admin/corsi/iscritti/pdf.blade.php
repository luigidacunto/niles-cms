<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h1 { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Iscritti — {{ $corso->tipologia->nome }} ({{ $corso->protocollo }})</h1>
    <p>{{ $iscrizioni->count() }} iscritti totali — generato il {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Codice fiscale</th>
                <th>Fatturazione a</th>
                <th>Metodo pagamento</th>
                <th>Presenza</th>
                <th>Iscritto il</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($iscrizioni as $iscrizione)
                <tr>
                    <td>{{ $iscrizione->nome }} {{ $iscrizione->cognome }}</td>
                    <td>{{ $iscrizione->email }}</td>
                    <td>{{ $iscrizione->telefono }}</td>
                    <td>{{ $iscrizione->codice_fiscale }}</td>
                    <td>
                        {{ $iscrizione->datiFatturazione->tipo === 'azienda'
                            ? $iscrizione->datiFatturazione->ragione_sociale
                            : trim($iscrizione->datiFatturazione->nome.' '.$iscrizione->datiFatturazione->cognome) }}
                    </td>
                    <td>{{ $iscrizione->datiFatturazione->metodo_pagamento }}</td>
                    <td>{{ $iscrizione->presenza_confermata_at ? 'Confermata '.$iscrizione->presenza_confermata_at->format('d/m') : '—' }}</td>
                    <td>{{ $iscrizione->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
